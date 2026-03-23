<?php

use App\Mcp\Servers\CmsServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/cms', CmsServer::class);

Mcp::local('cms', CmsServer::class);
