<?php

namespace App\Enums;

enum StatusChangeActor: string
{
    case Visitor = 'visitor';
    case Operator = 'operator';
    case System = 'system';
}
