<?php

namespace App\Enums;

enum NodeType: string
{
    case Page = 'page';
    case Menu = 'menu';
    case Setting = 'setting';
    case Component = 'component';
}
