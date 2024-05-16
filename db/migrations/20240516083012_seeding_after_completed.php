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
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
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
