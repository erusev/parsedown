<?php

namespace Erusev\Parsedown;

/**
 * Beware that the values of MutableConfigurables are NOT stable. Values SHOULD
 * be accessed as close to use as possible. Parsing operations sharing the same
 * State SHOULD NOT be triggered between where values are read and where they
 * need to be relied upon.
 */
interface MutableConfigurable extends Configurable
{
    /**
     * Objects contained in State can generally be regarded as immutable,
     * however, when mutability is *required* then isolatedCopy (this method)
     * MUST be implemented to take a reliable copy of the contained state,
     * which MUST be fully seperable from the current instance. This is
     * sometimes referred to as a "deep copy".
     *
     * The following assumption is made when you implement
     * MutableConfigurable:
     *
     *   A shared, (more or less) globally writable, instantaniously updating
     *   (at all parsing levels), single copy of a Configurable is intentional
     *   and desired.
     *
     * As such, Parsedown will use the isolatedCopy method to ensure state
     * isolation between successive parsing calls (which are considered to be
     * isolated documents).
     *
     * You MUST NOT depend on the method `initial` being called when a clean
     * parsing state is desired, this will not reliably occur; implement
     * isolatedCopy properly to allow Parsedown to manage this.
     *
     * Failing to implement this method properly can result in unintended
     * side-effects. If possible, you should design your Configurable to be
     * immutable, which allows a single copy to be shared safely, and mutations
     * localised to a heirarchy for which the order of operations is easy to
     * reason about.
     *
     * @return static
     */
    public function isolatedCopy();
}
