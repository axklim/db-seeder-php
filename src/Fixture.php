<?php

declare(strict_types=1);

namespace SeederGenerator;

interface Fixture
{
    public function id(): mixed; // TODO: rename to getId. Implement in fixtures depending on [Key: PRI]
    public function tableName(): string;
    public function fields(): array;
    public function values(): array;
    /** @return Fixture[] */
    public function dependencies(): array;
}
