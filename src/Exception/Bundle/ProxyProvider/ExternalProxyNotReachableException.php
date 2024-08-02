<?php

namespace JobSearcher\Exception\Bundle\ProxyProvider;

use Exception;

/**
 * Indicates that external proxy services are not reachable.
 * It was planned that this exception should INSTA break the extraction and mark it as failed.
 *
 * That's pro-user way. Also, this way it's just an assumption that some extraction went wrong.
 * Ofc. it won't mean that whole extraction went wrong, but it's just easier to go this way.
 *
 * This will ofc. be invalid if something was extracted - money lost in this case (for project, not customer), but
 * at least it will decrease risk of ppl complaining that some money was taken.
 */
class ExternalProxyNotReachableException extends Exception
{

}