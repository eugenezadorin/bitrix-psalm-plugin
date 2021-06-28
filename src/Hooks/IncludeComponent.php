<?php

namespace Zadorin\BitrixPsalmPlugin\Hooks;

use Zadorin\BitrixPsalmPlugin\Plugin;
use Psalm\Plugin\EventHandler\Event\AfterMethodCallAnalysisEvent;
use Psalm\Plugin\EventHandler\AfterMethodCallAnalysisInterface;

final class IncludeComponent extends Hook implements AfterMethodCallAnalysisInterface
{
    /** @var AfterMethodCallAnalysisEvent */
    public $event;

    /** @var string */
    public $componentName;

    public static function afterMethodCallAnalysis(AfterMethodCallAnalysisEvent $event): void
    {
        $method = strtolower($event->getMethodId());

        if ($method === 'cbitrixcomponent::includecomponentclass') {
            (new static($event))();
        }
    }

    public function __construct(AfterMethodCallAnalysisEvent $event)
    {
        $arguments = $this->extractArgumentsList($event);
        
        $this->componentName = $arguments[0];
        $this->event = $event;
    }

    public function __invoke()
    {
        if (!$this->isValidComponentName()) {
            return;
        }
        
        $component = new \CBitrixComponent;
        if (!$component->initComponent($this->componentName)) {
            return;
        }
        
        $absPath = sprintf('%s/%s/class.php', Plugin::getDocumentRoot(), $component->__path);
        if (file_exists($absPath)) {
            $codebase = $this->event->getCodebase();
            $codebase->reloadFiles(\Psalm\Internal\Analyzer\ProjectAnalyzer::getInstance(), [$absPath]);
        }
    }

    public function isValidComponentName(): bool
    {
        if (is_string($this->componentName) && strpos($this->componentName, ':') > 0) {
            return true;
        }

        return false;
    }
}
