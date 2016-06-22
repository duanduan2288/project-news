<?php

/*
 * This file is part of the overtrue/wechat.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * StaffServiceProvider.php.
 *
 * This file is part of the wechat.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace EasyWeChat\Foundation\ServiceProviders;

use EasyWeChat\Staff\Staff;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * Class StaffServiceProvider.
 */
class StaffServiceProvider implements ServiceProviderInterface
{
    /**
     * Registers Service on the given container.
     *
     * This method should only be used to configure Service and parameters.
     * It should not get Service.
     *
     * @param Container $pimple A container instance
     */
    public function register(Container $pimple)
    {
        $pimple['staff'] = function ($pimple) {
            return new Staff($pimple['access_token']);
        };
    }
}
