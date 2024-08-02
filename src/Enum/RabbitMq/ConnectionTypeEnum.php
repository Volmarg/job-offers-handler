<?php

namespace JobSearcher\Enum\RabbitMq;

/**
 * {@link http://localhost:15672/#/exchanges}
 */
enum ConnectionTypeEnum: string
{
    case DIRECT  = "direct";
    case FANOUT  = "fanout";
    case HEADERS = "headers";
    case MATCH   = "match";
    case TRACE   = "rabbitmq.trace";
    case TOPIC   = "topic";
}