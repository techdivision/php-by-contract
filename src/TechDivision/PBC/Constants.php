<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */

// The constants for our annotations
define('PBC_KEYWORD_PRE', '@requires');
define('PBC_KEYWORD_POST', '@ensures');
define('PBC_KEYWORD_INVARIANT', '@invariant');

// Some keywords we need for our constructed code
define('PBC_KEYWORD_OLD', '$pbcOld');
define('PBC_KEYWORD_RESULT', '$pbcResult');
define('PBC_CONTRACT_DEPTH', 'pbcContractDepth');
define('PBC_MARK_CONTRACT_ENTRY', '$pbcContractEntry');

define('PBC_CLASS_INVARIANT_NAME', 'pbcClassInvariant');
define('PBC_ORIGINAL_FUNCTION_SUFFIX', 'PBCOriginal');

// Constants to configure our structure maps and caches
define('PBC_MAP_DIR', __DIR__ . DIRECTORY_SEPARATOR . 'cache');
define('PBC_CACHE_DIR', __DIR__ . DIRECTORY_SEPARATOR . 'cache');

// Will be used as placeholder for code procession
define('PBC_PROCESSING_PLACEHOLDER', '/* PBC_PROCESSING_PLACEHOLDER ');
define('PBC_PRECONDITION_PLACEHOLDER', '/* PBC_PRECONDITION_PLACEHOLDER ');
define('PBC_POSTCONDITION_PLACEHOLDER', '/* PBC_POSTCONDITION_PLACEHOLDER ');
define('PBC_INVARIANT_PLACEHOLDER', '/* PBC_INVARIANT_PLACEHOLDER ');
define('PBC_OLD_SETUP_PLACEHOLDER', '/* PBC_OLD_SETUP_PLACEHOLDER ');
define('PBC_PLACEHOLDER_CLOSE', ' */');
define('PBC_FAILURE_VARIABLE', '$pbcFailureMessage');

// We might not have a PHP > 5.3 on our hands.
// To avoid parser errors we will define used constants here
if (!defined('T_TRAIT')) {
    define('T_TRAIT', 355);
}