<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierBankAccount extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = ['supplier_name', 'bank_name', 'account_no', 'swift_code'];
}
