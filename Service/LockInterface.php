<?php


namespace JordiLlonch\Bundle\DeployBundle\Service;


interface LockInterface {
    public function acquire($id);
    public function release($id);
    public function releaseAll();
}