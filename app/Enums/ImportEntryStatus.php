<?php

namespace App\Enums;

enum ImportEntryStatus: string
{
    case Completed = 'completed';
    case Failed = 'failed';
}
