<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\AppRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'phone', 'avatar_url', 'is_active', 'wechat_openid', 'wechat_unionid'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        return AppRole::canAccessAdmin($this->role);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(RecipeHistory::class);
    }

    public function circlePosts(): HasMany
    {
        return $this->hasMany(CirclePost::class);
    }

    public function circleComments(): HasMany
    {
        return $this->hasMany(CircleComment::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function paymentOrders(): HasMany
    {
        return $this->hasMany(PaymentOrder::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function dailyStatuses(): HasMany
    {
        return $this->hasMany(UserDailyStatus::class);
    }

    public function ensureProfile(): UserProfile
    {
        return UserProfile::query()->firstOrCreate(
            ['user_id' => $this->id],
            ['gender' => 'unknown'],
        );
    }

    protected function role(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => AppRole::normalize($value),
            set: fn (?string $value) => AppRole::normalize($value),
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }
}
