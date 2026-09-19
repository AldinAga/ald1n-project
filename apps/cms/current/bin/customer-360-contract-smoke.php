<?php

declare(strict_types=1);

use App\Models\CustomerCrmNote;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$checks = 0;
$failures = 0;
$check = static function (bool $condition, string $message) use (&$checks, &$failures): void {
    $checks++;
    if ($condition) {
        echo 'PASS '.$message.PHP_EOL;
        return;
    }
    $failures++;
    echo 'FAIL '.$message.PHP_EOL;
};

$tableExists = Schema::hasTable('customer_crm_notes');
$modelExists = class_exists(CustomerCrmNote::class);
$userHasRelation = method_exists(User::class, 'crmNotes');
$model = $modelExists ? new CustomerCrmNote() : null;
$userRelation = $userHasRelation ? (new User())->crmNotes() : null;
$customerRelation = $modelExists ? $model->customer() : null;
$authorRelation = $modelExists ? $model->author() : null;
$casts = $modelExists ? $model->getCasts() : [];
$routesSource = file_get_contents(dirname(__DIR__).'/routes/api.php');
$routesSource = is_string($routesSource) ? $routesSource : '';

$check($tableExists, 'customer_crm_notes table exists');
$check($modelExists, 'CustomerCrmNote model exists');
$check($userRelation instanceof HasMany && $userRelation->getForeignKeyName() === 'user_id', 'User exposes crmNotes HasMany relation');
$check($modelExists && $model->getTable() === 'customer_crm_notes', 'CustomerCrmNote maps explicit table');
$check($modelExists && $model->getFillable() === ['user_id', 'author_user_id', 'body'], 'CustomerCrmNote fillable is exact');
$check($modelExists && ($casts['user_id'] ?? null) === 'integer' && ($casts['author_user_id'] ?? null) === 'integer', 'CustomerCrmNote foreign IDs cast to integer');
$check($customerRelation instanceof BelongsTo && $customerRelation->getForeignKeyName() === 'user_id', 'CustomerCrmNote customer relation uses user_id');
$check($authorRelation instanceof BelongsTo && $authorRelation->getForeignKeyName() === 'author_user_id', 'CustomerCrmNote author relation uses author_user_id');
$check($modelExists && !in_array(SoftDeletes::class, class_uses_recursive(CustomerCrmNote::class), true), 'CustomerCrmNote has no soft delete contract');
$check($tableExists && !Schema::hasColumn('customer_crm_notes', 'deleted_at'), 'customer_crm_notes has no deleted_at column');
$check(!str_contains($routesSource, 'customer-portal.users.crm-notes.update'), 'No CRM note update route exists');
$check(!str_contains($routesSource, 'customer-portal.users.crm-notes.destroy'), 'No CRM note delete route exists');

echo 'CUSTOMER360_CONTRACT_SMOKE='.$checks.'_CHECKS_'.($checks - $failures).'_PASS_'.$failures.'_FAIL'.PHP_EOL;
exit($failures === 0 ? 0 : 1);
