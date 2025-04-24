<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Employee extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'employee_test';

    protected $fillable = [
        'photo',
        'fname',
        'mname',
        'lname',
        'gender',
        'hobbies',
        'address',
        'email',
        'number',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'hobbies' => 'array',
        'password' => 'hashed',
    ];
}
