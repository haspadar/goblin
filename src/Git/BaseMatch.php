<?php

declare(strict_types=1);

namespace Goblin\Git;

/**
 * Strategy for comparing the parent branch against declared bases.
 */
enum BaseMatch
{
    case Strict;
    case Transitive;
}
