<?php

namespace App\Enums;

enum ImportTrigger: string
{
    case Schedule = 'schedule';
    case Manual = 'manual';
}
