<?php

namespace Zadorin\BitrixPsalmPlugin\Hooks;

use Zadorin\BitrixPsalmPlugin\UnknownArgument;
use Psalm\Plugin\EventHandler\Event\AfterMethodCallAnalysisEvent;

abstract class Hook
{
    public function extractArgumentsList(AfterMethodCallAnalysisEvent $event): array
    {
        $list = [];
        $expr = $event->getExpr();
        $context = $event->getContext();

        foreach ($expr->args as $arg) {

            $candidate = new UnknownArgument($arg);
            
            if ($arg->value instanceof \PhpParser\Node\Scalar\String_) {
                $candidate = $arg->value->value;
            } elseif ($arg->value instanceof \PhpParser\Node\Expr\Variable) {
                $varName = '$' . $arg->value->name;
                if (isset($context->vars_in_scope[$varName])) {
                    $var = $context->vars_in_scope[$varName];
                    if ($var->isSingleStringLiteral()) {
                        $candidate = $var->getSingleStringLiteral()->value;
                    }
                }
            }

            $list[] = $candidate;
        }

        return $list;
    }
}
