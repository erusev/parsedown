<?php

namespace Erusev\Parsedown\Components;

use Erusev\Parsedown\Component;
use Erusev\Parsedown\Html\Renderables\Text;
use Erusev\Parsedown\Parsing\Excerpt;
use Erusev\Parsedown\State;

interface Inline extends Component
{
    /**
     * @param Excerpt $Excerpt
     * @param State $State
     * @return static|null
     */
    public static function build(Excerpt $Excerpt, State $State);

    /**
     * Number of characters consumed by the build action.
     * @return int
     * */
    public function width();

    /**
     * @return Text
     */
    public function bestPlaintext();
}
