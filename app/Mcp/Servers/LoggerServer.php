<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\CreateProjectTool;
use App\Mcp\Tools\GetIngestConfigTool;
use App\Mcp\Tools\GetLogTool;
use App\Mcp\Tools\ListProjectsTool;
use App\Mcp\Tools\NewLogGroupsTool;
use App\Mcp\Tools\ProjectOverviewTool;
use App\Mcp\Tools\ResolveLogGroupTool;
use App\Mcp\Tools\SearchLogsTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Logger')]
#[Version('1.0.0')]
#[Instructions('Central log aggregation. Triage: list_projects, then new_log_groups for what is new, search_logs to drill down, get_log for the stack trace, resolve_log_group once a group is fixed (kept, hidden from triage, recurrences show as new). Onboarding: create_project, then get_ingest_config for endpoint and key. Project keys are secrets: only write them to the target app\'s .env.')]
class LoggerServer extends Server
{
    protected array $tools = [
        ListProjectsTool::class,
        ProjectOverviewTool::class,
        NewLogGroupsTool::class,
        SearchLogsTool::class,
        GetLogTool::class,
        ResolveLogGroupTool::class,
        CreateProjectTool::class,
        GetIngestConfigTool::class,
    ];

    protected array $resources = [];

    protected array $prompts = [];
}
