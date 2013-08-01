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

class FilesHelper extends Helper {
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'files';
    }

    public function filesReplacePattern(array $paths, $pattern, $replacement)
    {
        $errors = array();
        foreach ($paths as $path) {
            if (!file_exists($path)) {
                $error = 'File "' . $path . '" does not exists.';
                //self::log $error . "\n";
                $errors[] = $error;

                continue;
            }
            $content = file_get_contents($path);
            $content = preg_replace($pattern, $replacement, $content);
            file_put_contents($path, $content);
        }

        if (count($errors)) return $errors;

        return true;
    }
}