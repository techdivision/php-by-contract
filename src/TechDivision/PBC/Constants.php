<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 20.06.13
 * Time: 09:18
 * To change this template use File | Settings | File Templates.
 */

define('PBC_KEYWORD_PRE', '@requires');
define('PBC_KEYWORD_POST', '@ensures');
define('PBC_KEYWORD_INVARIANT', '@invariant');
define('PBC_KEYWORD_OLD', 'pbcOld');
define('PBC_KEYWORD_RESULT', '$pbcResult');
define('PBC_CONTRACT_DEPTH', 'pbcContractDepth');
define('PBC_MARK_CONTRACT_ENTRY', '$pbcContractEntry');
define('PBC_PROXY_SUFFIX', 'PBCProxied');
define('PBC_CLASS_INVARIANT_NAME', 'pbcClassInvariant');
define('PBC_ORIGINAL_FUNCTION_SUFFIX', 'PBCOriginal');
define('PBC_MAP_DIR', __DIR__ . DIRECTORY_SEPARATOR . 'Proxies' . DIRECTORY_SEPARATOR . 'cache');
define('PBC_CACHE_DIR', __DIR__ . DIRECTORY_SEPARATOR . 'Proxies' . DIRECTORY_SEPARATOR . 'cache');

// We might not have a PHP > 5.3 on our hands.
// To avoid parser errors we will define used constants here
if (!defined('T_TRAIT')) {
    define('T_TRAIT', 355);
}