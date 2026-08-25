<?php

namespace Hubleto\App\Custom\Trainings;

use Hubleto\App\Community\Auth\Models\User;
use Hubleto\App\Community\Auth\Models\UserRole;
use Hubleto\App\Community\Auth\Models\UserHasRole;

/**
 * Resolves the "administrative users" who should be notified about new
 * training orders and undelivered retraining reminders: administrator-type
 * users plus anyone holding a role with grant_all permissions.
 */
class AdminUsers extends \Hubleto\Erp\Core
{
  public function getAdminUserIds(): array
  {
    /** @var User */
    $mUser = $this->getModel(User::class);
    $adminByType = $mUser->record->where('type', User::TYPE_ADMINISTRATOR)->pluck('id')->all();

    /** @var UserRole */
    $mUserRole = $this->getModel(UserRole::class);
    $grantAllRoleIds = $mUserRole->record->where('grant_all', true)->pluck('id')->all();

    $adminByRole = [];
    if (!empty($grantAllRoleIds)) {
      /** @var UserHasRole */
      $mUserHasRole = $this->getModel(UserHasRole::class);
      $adminByRole = $mUserHasRole->record->whereIn('id_role', $grantAllRoleIds)->pluck('id_user')->all();
    }

    return array_values(array_unique(array_map('intval', array_merge($adminByType, $adminByRole))));
  }
}
