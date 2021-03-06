<?php
/**
 * \PHPCompatibility\Sniffs\FunctionNameRestrictions\ReservedFunctionNamesSniff.
 *
 * @category PHP
 * @package  PHPCompatibility
 * @author   Juliette Reinders Folmer <phpcompatibility_nospam@adviesenzo.nl>
 */

namespace PHPCompatibility\Sniffs\FunctionNameRestrictions;

use PHPCompatibility\PHPCSHelper;

/**
 * \PHPCompatibility\Sniffs\FunctionNameRestrictions\ReservedFunctionNamesSniff.
 *
 * All function and method names starting with double underscore are reserved by PHP.
 *
 * {@internal Extends an upstream sniff to benefit from the properties contained therein.
 *            The properties are lists of valid PHP magic function and method names, which
 *            should be ignored for the purposes of this sniff.
 *            As this sniff is not PHP version specific, we don't need access to the utility
 *            methods in the PHPCompatibility\Sniff, so extending the upstream sniff is fine.
 *            As the upstream sniff checks the same (and more, but we don't need the rest),
 *            the logic in this sniff is largely the same as used upstream.
 *            Extending the upstream sniff instead of including it via the ruleset, however,
 *            prevents hard to debug issues of errors not being reported from the upstream sniff
 *            if this library is used in combination with other rulesets.}}
 *
 * @category PHP
 * @package  PHPCompatibility
 * @author   Juliette Reinders Folmer <phpcompatibility_nospam@adviesenzo.nl>
 */
class ReservedFunctionNamesSniff extends \Generic_Sniffs_NamingConventions_CamelCapsFunctionNameSniff
{

    /**
     * Overload the constructor to work round various PHPCS cross-version compatibility issues.
     */
    public function __construct()
    {
        $scopeTokens = array(T_CLASS, T_INTERFACE, T_TRAIT);
        if (defined('T_ANON_CLASS')) {
            $scopeTokens[] = T_ANON_CLASS;
        }

        // Call the grand-parent constructor directly.
        \PHP_CodeSniffer_Standards_AbstractScopeSniff::__construct($scopeTokens, array(T_FUNCTION), true);

        // Make sure debuginfo is included in the array. Upstream only includes it since 2.5.1.
        $this->magicMethods['debuginfo'] = true;
    }


    /**
     * Processes the tokens within the scope.
     *
     * @param \PHP_CodeSniffer_File $phpcsFile The file being processed.
     * @param int                   $stackPtr  The position where this token was
     *                                         found.
     * @param int                   $currScope The position of the current scope.
     *
     * @return void
     */
    protected function processTokenWithinScope(\PHP_CodeSniffer_File $phpcsFile, $stackPtr, $currScope)
    {
        $tokens = $phpcsFile->getTokens();

        $methodName = $phpcsFile->getDeclarationName($stackPtr);
        if ($methodName === null) {
            // Ignore closures.
            return;
        }

        // Is this a magic method. i.e., is prefixed with "__" ?
        if (preg_match('|^__[^_]|', $methodName) > 0) {
            $magicPart = strtolower(substr($methodName, 2));
            if (isset($this->magicMethods[$magicPart]) === false
                && isset($this->methodsDoubleUnderscore[$magicPart]) === false
            ) {
                $className = '[anonymous class]';
                if (defined('T_ANON_CLASS') === false || $tokens[$currScope]['type'] !== 'T_ANON_CLASS') {
                    $className = $phpcsFile->getDeclarationName($currScope);
                }

                $phpcsFile->addWarning(
                    'Method name "%s" is discouraged; PHP has reserved all method names with a double underscore prefix for future use.',
                    $stackPtr,
                    'MethodDoubleUnderscore',
                    array($className . '::' . $methodName)
                );
            }
        }
    }


    /**
     * Processes the tokens outside the scope.
     *
     * @param \PHP_CodeSniffer_File $phpcsFile The file being processed.
     * @param int                   $stackPtr  The position where this token was
     *                                         found.
     *
     * @return void
     */
    protected function processTokenOutsideScope(\PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $functionName = $phpcsFile->getDeclarationName($stackPtr);
        if ($functionName === null) {
            // Ignore closures.
            return;
        }

        // Is this a magic function. i.e., it is prefixed with "__".
        if (preg_match('|^__[^_]|', $functionName) > 0) {
            $magicPart = strtolower(substr($functionName, 2));
            if (isset($this->magicFunctions[$magicPart]) === false) {
                $phpcsFile->addWarning(
                    'Function name "%s" is discouraged; PHP has reserved all method names with a double underscore prefix for future use.',
                    $stackPtr,
                    'FunctionDoubleUnderscore',
                    array($functionName)
                );
            }
        }
    }
}
