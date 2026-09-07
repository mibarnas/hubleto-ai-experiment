<?php

namespace HubletoProject\Dependency;

/**
 * Base migration with the helpers this project's schema clean-ups need.
 *
 * Several models had columns removed (ownership on trainings and orders, the
 * lecturer on a schedule, the denormalised counters). Dropping those columns
 * is not a plain `DROP COLUMN`: MySQL refuses while a foreign key still backs
 * the column, and the constraint names are hashes generated when the table was
 * first created, so they have to be looked up rather than hard-coded.
 *
 * MySQL has no `IF [NOT] EXISTS` for columns or indexes (that is a MariaDB
 * extension), so every helper checks information_schema first. This is what
 * makes the migrations safe to run against a database that is already partly
 * up to date.
 */
abstract class Migration extends \Hubleto\Framework\Migration
{
  /**
   * Drops the listed columns together with any foreign key backing them.
   * Columns that are already gone are skipped.
   *
   * @param string[] $columns
   */
  protected function dropColumns(string $table, array $columns): void
  {
    foreach ($columns as $column) {
      if (!$this->hasColumn($table, $column)) continue;
      $this->dropForeignKeysOfColumn($table, $column);
      $this->db->execute("alter table `{$table}` drop column `{$column}`;");
    }
  }

  protected function dropForeignKeysOfColumn(string $table, string $column): void
  {
    $constraints = (array) $this->db->fetchAll("
      select `CONSTRAINT_NAME` as `name`
      from `information_schema`.`KEY_COLUMN_USAGE`
      where `TABLE_SCHEMA` = database()
        and `TABLE_NAME` = ?
        and `COLUMN_NAME` = ?
        and `REFERENCED_TABLE_NAME` is not null
    ", [$table, $column]);

    foreach ($constraints as $constraint) {
      $name = $constraint['name'] ?? '';
      if ($name === '') continue;
      $this->db->execute("alter table `{$table}` drop foreign key `{$name}`;");
    }
  }

  /**
   * Adds a column only when it is not there yet, so a migration can bring an
   * older installation forward without failing on a fresh one.
   */
  protected function addColumn(string $table, string $column, string $definition): void
  {
    if ($this->hasColumn($table, $column)) return;
    $this->db->execute("alter table `{$table}` add column `{$column}` {$definition};");
  }

  protected function addIndex(string $table, string $index, string $columns): void
  {
    if ($this->hasIndex($table, $index)) return;
    $this->db->execute("create index `{$index}` on `{$table}` ({$columns});");
  }

  protected function hasColumn(string $table, string $column): bool
  {
    $row = $this->db->fetchFirst("
      select 1 as `found`
      from `information_schema`.`COLUMNS`
      where `TABLE_SCHEMA` = database() and `TABLE_NAME` = ? and `COLUMN_NAME` = ?
    ", [$table, $column]);

    return !empty($row);
  }

  protected function hasIndex(string $table, string $index): bool
  {
    $row = $this->db->fetchFirst("
      select 1 as `found`
      from `information_schema`.`STATISTICS`
      where `TABLE_SCHEMA` = database() and `TABLE_NAME` = ? and `INDEX_NAME` = ?
    ", [$table, $index]);

    return !empty($row);
  }
}
