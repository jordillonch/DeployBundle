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

class HipChatHelper extends Helper {
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'hipchat';
    }

    /**
     * Send a message to a given room
     * You must set your token and room_id to your HipChat in jordi_llonch_deploy.general parameters:
     * helper:
     *     hipchat:
     *         token: your_token
     *         room_id: your_room_id
     * @param $msg
     * @param string $color
     * @throws \Exception
     */
    public function send($msg, $color='purple')
    {
        $helperConfig = $this->getConfig();
        if(!isset($helperConfig['token'])) throw new \Exception('Helper HipChat token is not configured in parameters.yml');
        if(!isset($helperConfig['room_id'])) throw new \Exception('Helper HipChat room_id is not configured in parameters.yml');
        $allowedColors = array('yellow', 'red', 'green', 'purple', 'gray', 'random');
        if(!in_array($color, $allowedColors)) throw new \Exception('HipChat helper, background color for message. One of "yellow", "red", "green", "purple", "gray", or "random"');
        
        $msg = nl2br($msg);
        if(is_object($this->getDeployer())) $this->getDeployer()->getLogger()->debug('[helperHipChat] send: "' . $msg . '"');
        $encodedMsg = urlencode($msg);
        $ch = curl_init('https://api.hipchat.com/v1/rooms/message?auth_token=' . $helperConfig['token'] . '&format=json');
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'room_id=' . $helperConfig['room_id'] . '&from=Deploy&message=' . $encodedMsg . '&notify=0&color=' . $color);
        curl_exec($ch);
        curl_close($ch);
    }
}