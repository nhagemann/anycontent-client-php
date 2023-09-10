<?php

declare(strict_types=1);

namespace AnyContent\Connection\Interfaces;

use CMDL\ConfigTypeDefinition;
use CMDL\ContentTypeDefinition;
use DateTimeInterface;

interface RevisionWriteConnection extends RevisionConnection
{
    public function truncateContentTypeRevisions(ContentTypeDefinition $contentTypeDefinition, DateTimeInterface $endDate): void;

    public function truncateConfigTypeRevisions(ConfigTypeDefinition $configTypeDefinition, DateTimeInterface $endDate): void;
}
