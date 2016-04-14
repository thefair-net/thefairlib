<?php

/**
 * Inter.php
 *
 * @author liumingzhi
 * @version 1.0
 * @copyright 2015-2015
 * @date 16/4/14 下午2:20
 */
namespace TheFairLib\Queue;

interface Inter
{

    //生产者
    public function produce($topicName, $msg = []);

    //消费者
    public function consumer($topicName);
}