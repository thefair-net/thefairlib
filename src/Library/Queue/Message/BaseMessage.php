<?php

/**
 * File: BaseMessage.php
 * File Created: Thursday, 28th May 2020 6:00:07 pm
 * Author: Yin
 */

namespace TheFairLib\Library\Queue\Message;

/**
 * 队列消息实体
 */
abstract class BaseMessage
{
    /**
     * 消息投递时间戳（秒）
     *
     * @var int
     */
    protected $startDeliverTime;

    /**
     * 消息类型
     *
     * @var string
     */
    protected $messageType;

    /**
     * 消息Tag
     *
     * @var string
     */
    protected $messageTag;

    /**
     * 原始数据
     *
     * @var string
     */
    protected $origin;

    /**
     * 初始化
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        if ($data) {
            $this->origin = encode($data);

            foreach ($data as $key => $value) {
                if (empty($value)) {
					continue;
                }

                $func = 'set' . ucwords(camelize($key));

                if (method_exists($this, $func)) {
                    $this->$func($value);
                }
            }
        }
    }

    /**
     * 对象转str，发送
     *
     * @return string
     */
    public function toString(): string
    {
        return encode(get_object_vars($this));
    }

    public function __toString()
    {
        return $this->toString();
    }

    /**
     * Get the value of startDeliverTime
     *
     * @return mixed
     */
    public function getStartDeliverTime()
    {
        return $this->startDeliverTime;
    }

    /**
     * Set the value of startDeliverTime
     *
     * @param int $startDeliverTime
     *
     * @return self
     */
    public function setStartDeliverTime($startDeliverTime)
    {
        $this->startDeliverTime = $startDeliverTime;

        return $this;
    }

    /**
     * Get 消息类型
     *
     * @return string
     */
    public function getMessageType()
    {
        return $this->messageType;
    }

    /**
     * Set 消息类型
     *
     * @param string $messageType 消息类型
     *
     * @return self
     */
    public function setMessageType(string $messageType)
    {
        $this->messageType = $messageType;

        return $this;
    }


    /**
     * Get 消息Tag
     *
     * @return string
     */
    public function getMessageTag()
    {
        return $this->messageTag;
    }

    /**
     * Set 消息Tag
     *
     * @param string $messageTag 消息Tag
     *
     * @return self
     */
    public function setMessageTag(string $messageTag)
    {
        $this->messageTag = $messageTag;

        return $this;
    }

    /**
     * Get 原始数据
     *
     * @return string
     */
    public function getOrigin()
    {
        return $this->origin;
    }

    /**
     * Set 原始数据
     *
     * @param string $origin 原始数据
     *
     * @return self
     */
    public function setOrigin(string $origin)
    {
        $this->origin = $origin;

        return $this;
    }
}
