<?php
/**
 * This file is part of the JordiLlonchDeployBundle
 *
 * (c) Jordi Llonch <llonch.jordi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JordiLlonch\Bundle\DeployBundle\Helpers;

class GitHubHelper extends Helper {
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'github';
    }

    /**
     * Give an url comparing two commits
     * You must set your http url to your GitHub repository in jordi_llonch_deploy.zones parameters:
     * helper:
     *     github:
     *         url: https://github.com/YourUser/Repository
     * @param string $gitUidFrom
     * @param string $gitUidTo
     * @return string
     */
    public function getCompareUrl($gitUidFrom, $gitUidTo)
    {
        $helperConfig = $this->getConfig();
        if(!isset($helperConfig['url'])) throw new \Exception('Helper GitHub url is not configured in parameters.yml');

        if ($gitUidFrom == $gitUidTo) $url = false;
        else $url = $helperConfig['url'] . '/compare/' . $gitUidFrom . '...' . $gitUidTo;

        return $url;
    }

    /**
     * Give an url comparing commits between current running code and the new downloaded code
     * @return string
     */
    public function getCompareUrlFromCurrentCodeToNewRepository()
    {
        list($gitUidFrom, $gitUidTo) = $this->getDeployer()->getHashFromCurrentCodeToNewRepository();
        $url = $this->getCompareUrl($gitUidFrom, $gitUidTo);

        return $url;
    }
}