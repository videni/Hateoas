<?php

namespace Hateoas\Serializer\Metadata;

use JMS\Serializer\SerializationContext;
/**
 * @author Adrien Brault <adrien.brault@gmail.com>
 */
class InlineDeferrer
{
    /**
     * @var \SplObjectStorage
     */
    protected $deferredData;

    public function __construct()
    {
        $this->deferredData = new \SplObjectStorage();
    }

    public function handleItems($object, array $items, SerializationContext $context)
    {
        if ($this->deferredData->contains($object)) {
            $items = array_merge($this->deferredData->offsetGet($object), $items);
            $this->deferredData->detach($object);
        }

        $parentObjectInlining = $this->getParentObjectInlining($object, $context);
        if (null === $parentObjectInlining) {
            return $items;
        }

        if ($this->deferredData->contains($parentObjectInlining)) {
            $items = array_merge($items, $this->deferredData->offsetGet($parentObjectInlining));
        }

        // We need to defer the links serialization to the $parentObject
        $this->deferredData->attach($parentObjectInlining, $items);

        return array();
    }

    private function getParentObjectInlining($object, SerializationContext $context)
    {
        $metadataStack = $context->getMetadataStack();
        $visitingStack = $context->getVisitingStack();

        $parentObject = null;
        if (count($visitingStack) > 0) {
            $parentObject = $visitingStack[0];
        }
        if ($parentObject === $object && count($visitingStack) > 1) {
            $parentObject = $visitingStack[1]; // $object is inlined inside $parentObject
        }

        if (
            $metadataStack->count() > 0 && isset($metadataStack[0]->inline) && $metadataStack[0]->inline
            && $context->getMetadataFactory()->getMetadataForClass(get_class($parentObject)) === $metadataStack[1]
        ) {
            return $parentObject;
        }

        return null;
    }
}
