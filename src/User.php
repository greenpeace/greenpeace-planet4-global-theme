<?php

namespace P4\MasterTheme;

use Timber;

/**
 * Class User extends Timber\User.
 *
 * Ref: https://timber.github.io/docs/reference/timber-user/
 */
class User extends Timber\User
{
    /**
     * Is a fake user flag
     *
     */
    public bool $is_fake = false;

    public static function build(\WP_User $wp_user): self
    {
        $user = parent::build($wp_user);
        return $user;
    }

    /**
     * The User profile page url.
     *
     */
    public function link(): string
    {
        if ($this->is_fake) {
            return '#';
        }

        return parent::link();
    }

    /**
     * The relative path of the User profile page.
     *
     */
    public function path(): string
    {
        if ($this->is_fake) {
            return '#';
        }

        return parent::path();
    }

    /**
     * Author display name.
     *
     */
    public function name(): ?string
    {
        if ($this->is_fake) {
            return (string) $this->display_name;
        }

        return parent::name();
    }

    /**
     * Stringifies the User object.
     */
    public function __toString(): string
    {
        if ($this->is_fake) {
            return $this->name();
        }

        return parent::__toString();
    }
}
