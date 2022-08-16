<?php

declare(strict_types=1);

namespace Gewis\Sniffs\General;

use PHP_CodeSniffer\Files\File as PHP_CodeSniffer_File;
use PHP_CodeSniffer\Sniffs\Sniff as PHP_CodeSniffer_Sniff;

class RequireConstructorPromotionSniff implements PHP_CodeSniffer_Sniff
{
    /**
     * All possible assignment tokens
     */
    private array $assignmentTokens = [
        'T_EQUAL',
        'T_AND_EQUAL',
        'T_OR_EQUAL',
        'T_CONCAT_EQUAL',
        'T_DIV_EQUAL',
        'T_MINUS_EQUAL',
        'T_POW_EQUAL',
        'T_MOD_EQUAL',
        'T_MUL_EQUAL',
        'T_PLUS_EQUAL',
        'T_XOR_EQUAL',
        'T_DOUBLE_ARROW',
        'T_SL_EQUAL',
        'T_SR_EQUAL',
        'T_COALESCE_EQUAL',
        'T_ZSR_EQUAL',
    ];

    /**
     * Returns the token types that this sniff is interested in.
     * @return array
     */
    public function register(): array
    {
        return [T_FUNCTION];
    }

    /**
     * Processes the tokens that this sniff is interested in.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file where the token was found.
     * @param int                  $stackPtr  The position in the stack where
     *                                        the token was found.
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens            = $phpcsFile->getTokens();
        $token             = $tokens[$stackPtr];
        $functionNameToken = $tokens[$stackPtr + 2];
        $functionName      = $functionNameToken['content'];

        if ('__construct' !== $functionName) {
            return;
        }
        // If this is an interface we don't check it.
        if (!isset($token['scope_opener'])) {
            return;
        }

        /**
         * We look within the constructor for an assignment
         * If we find it, we check whether it is an assignment to a subvariable of this
         * If there is something we don't expect in between,
         * we break the outer loop and assume there is no assignment
         * We also check if on the right hand side there is a variable and not an expression
         */
        for ($i = $token['scope_opener']; $i <= $token['scope_closer']; $i++) {
            if (in_array($tokens[$i]['type'], $this->assignmentTokens)) {
                $varName = null;
                $varScopeClass = false;
                $exprIsVariable = false;

                for ($j = $i - 1; $j > $token['scope_opener']; $j--) {
                    switch ($tokens[$j]['type']) {
                        case 'T_WHITESPACE':
                            break;
                        case 'T_STRING':
                            $varName = $tokens[$j]['content'];
                            break;
                        case 'T_OBJECT_OPERATOR':
                            if (
                                $tokens[$j - 1]['type'] === 'T_VARIABLE'
                                && $tokens[$j - 1]['content'] === '$this'
                            ) {
                                $varScopeClass = true;
                                break 2;
                            }
                            break;
                        default:
                            break 2;
                    }
                }

                for ($j = $i + 1; $j < $token['scope_closer']; $j++) {
                    switch ($tokens[$j]['type']) {
                        case 'T_WHITESPACE':
                            break;
                        case 'T_VARIABLE':
                            $exprIsVariable = true;
                            break 2;
                        default:
                            break 2;
                    }
                }

                if (
                    $varScopeClass === true
                    && $exprIsVariable === true
                ) {
                    // phpcs:ignore -- user-visible strings should not be split
                    $phpcsFile->addError("Class constructor MUST NOT contain assignments to class variables, but instead use constructor promotion. Assigning to \$this->$varName", $stackPtr, __CLASS__);
                }
            }
        }
    }
}
