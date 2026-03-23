<?php

namespace App\Enums;

enum NodeStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
