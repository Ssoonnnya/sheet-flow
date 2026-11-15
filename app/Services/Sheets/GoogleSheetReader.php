<?php

namespace App\Services\Sheets;

use App\Contracts\SheetReader;
use App\Exceptions\SheetFetchException;
use Google\Client;
use Google\Exception as GoogleException;
use Google\Service\Sheets;
use Google\Service\Sheets\Resource\SpreadsheetsValues;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use LogicException;

final class GoogleSheetReader implements SheetReader
{
    private ?Sheets $service = null;

    public function __construct(
        private readonly SheetGridParser $parser,
        private readonly string $credentialsPath,
        private readonly int $timeoutSeconds,
    ) {}

    public function rows(string $spreadsheetId, string $tabName, int $headerRow = 1): array
    {
        $spreadsheetId = SpreadsheetId::from($spreadsheetId);

        return $this->parser->rows(
            $this->fetch($spreadsheetId, $tabName),
            $headerRow,
        );
    }

    /**
     * @return list<list<mixed>>
     */
    private function fetch(string $spreadsheetId, string $tabName): array
    {
        if (! is_file($this->credentialsPath) || ! is_readable($this->credentialsPath)) {
            throw SheetFetchException::missingCredentials($this->credentialsPath);
        }

        try {
            $sheets = $this->service();

            /** @var SpreadsheetsValues $values */
            $values = $sheets->spreadsheets_values;

            $response = $values->get(
                $spreadsheetId,
                $this->range($tabName),
                [
                    'majorDimension' => 'ROWS',
                    'valueRenderOption' => 'FORMATTED_VALUE',
                    'dateTimeRenderOption' => 'FORMATTED_STRING',
                ],
            );
        } catch (GoogleException|GuzzleException|InvalidArgumentException|LogicException $exception) {
            throw SheetFetchException::failed($spreadsheetId, $tabName, $exception);
        }

        return $this->grid($response->getValues());
    }

    /**
     * Google documents this as an array, but an empty sheet leaves it null.
     *
     * @return list<list<mixed>>
     */
    private function grid(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        $grid = [];

        foreach ($values as $row) {
            if (! is_array($row)) {
                continue;
            }

            $grid[] = array_values($row);
        }

        return $grid;
    }

    private function service(): Sheets
    {
        if ($this->service instanceof Sheets) {
            return $this->service;
        }

        $client = new Client;
        $client->setApplicationName((string) config('app.name'));
        $client->setScopes([Sheets::SPREADSHEETS_READONLY]);
        $client->setAuthConfig($this->credentialsPath);
        $client->setHttpClient(new GuzzleClient([
            'timeout' => $this->timeoutSeconds,
            'connect_timeout' => min(10, $this->timeoutSeconds),
        ]));

        return $this->service = new Sheets($client);
    }

    private function range(string $tabName): string
    {
        return "'".str_replace("'", "''", $tabName)."'";
    }
}
