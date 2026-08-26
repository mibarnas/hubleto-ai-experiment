<?php

namespace Hubleto\App\Custom\Workers;

use Hubleto\App\Custom\Workers\Models\Worker;
use Hubleto\App\Custom\Trainings\Models\Certificate;

/**
 * Keeps Worker.date_next_retraining / id_next_retraining_training in sync
 * with the worker's certificates. These are denormalised (rather than a
 * virtual SQL column) so the Workers table can sort and filter on them.
 */
class RetrainingCalculator extends \Hubleto\Erp\Core
{
  public function recalculate(int $idWorker): void
  {
    /** @var Certificate */
    $mCertificate = $this->getModel(Certificate::class);

    $certificates = $mCertificate->record
      ->where('id_worker', $idWorker)
      ->whereNotNull('date_expiration')
      ->orderBy('date_expiration', 'asc')
      ->get();

    if ($certificates->isEmpty()) return;

    $today = date('Y-m-d');
    $upcoming = $certificates->first(fn($c) => $c->date_expiration >= $today);
    $chosen = $upcoming ?? $certificates->last();

    if (!$chosen) return;

    /** @var Worker */
    $mWorker = $this->getModel(Worker::class);
    $mWorker->record->find($idWorker)?->update([
      'date_next_retraining' => $chosen->date_expiration,
      'id_next_retraining_training' => $chosen->id_training,
    ]);
  }
}
