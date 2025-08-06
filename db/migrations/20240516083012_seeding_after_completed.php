<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

/**
 * 校验后做种
 */
final class SeedingAfterCompleted extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('cn_client');
        $isUpdate = false;
        if (!$table->hasColumn('seeding_after_completed')) {
            $isUpdate = true;
            $table->addColumn('seeding_after_completed', 'integer', ['limit' => MysqlAdapter::INT_TINY, 'default' => 1, 'null' => false, 'signed' => false, 'comment' => '校验后做种', 'after' => 'is_default']);
        }
        if ($isUpdate) {
            $table->update();
        }
    }
}
