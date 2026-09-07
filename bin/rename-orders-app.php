<?php

/**
 * One-off maintenance script for the `Orders` -> `TrainingOrders` rename.
 *
 * The custom orders app used to be `Hubleto\App\Custom\Orders` with the root
 * slug `orders`, which collided with the community Orders app: same routes,
 * same React component names. It is now `Hubleto\App\Custom\TrainingOrders`
 * under `training-orders`.
 *
 * Everything the rename touches in the database is a `config` row keyed by the
 * old class name. Those rows have to be carried over, and not only so the app
 * stays enabled: a model whose `installed-migration-tables` row is missing is
 * treated as never installed, and its first migration DROPs and recreates
 * `training_orders` -- which would destroy every existing order.
 *
 * The table itself is already called `training_orders` and does not change.
 *
 * Run once, from the project root:
 *
 *   php bin/rename-orders-app.php
 *
 * It is safe to run again: rows that have already been renamed are skipped.
 */

require_once(__DIR__ . '/../boot.php');

// A single backslash in PHP source, matching how the namespace is stored.
const OLD_NAMESPACE_FRAGMENT = 'Custom\\Orders';
const NEW_NAMESPACE_FRAGMENT = 'Custom\\TrainingOrders';

/** @var \Hubleto\Framework\Interfaces\DbInterface $db */
$db = $hubleto->db();

// In a LIKE pattern a backslash is itself an escape character, so the one in
// the namespace has to be doubled before it reaches MySQL.
$likePattern = '%' . str_replace('\\', '\\\\', OLD_NAMESPACE_FRAGMENT) . '%';
$rows = $db->fetchAll("select `id`, `path` from `config` where `path` like ?", [$likePattern]);

if (empty($rows)) {
  echo "Nothing to rename -- no config rows reference " . OLD_NAMESPACE_FRAGMENT . ".\n";
  exit(0);
}

$renamed = 0;
$skipped = 0;

foreach ($rows as $row) {
  $newPath = str_replace(OLD_NAMESPACE_FRAGMENT, NEW_NAMESPACE_FRAGMENT, $row['path']);

  if ($newPath === $row['path']) {
    // Nothing was substituted -- never treat that as "already renamed", or the
    // row would be deleted as a duplicate of itself.
    echo "leave  {$row['path']} (nothing to substitute)\n";
    continue;
  }

  $existing = $db->fetchFirst("select `id` from `config` where `path` = ?", [$newPath]);

  if ($existing) {
    // The new key is already there (the script ran before, or the app was
    // installed under its new name), so the stale row is simply dropped.
    $db->execute("delete from `config` where `id` = ?", [$row['id']]);
    $skipped++;
    echo "skip   {$row['path']} (target already exists)\n";
    continue;
  }

  $db->execute("update `config` set `path` = ? where `id` = ?", [$newPath, $row['id']]);
  $renamed++;
  echo "rename {$row['path']}\n";
  echo "    -> {$newPath}\n";
}

echo "\nRenamed {$renamed} config row(s), removed {$skipped} stale row(s).\n";
