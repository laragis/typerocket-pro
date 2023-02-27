<?php
namespace TypeRocket\Pro\Template;

use TypeRocket\Models\Model;
use TypeRocket\Template\Component;

class AdvancedComponent extends Component
{
    protected $cloneable = true;
    protected $nameable = true;

    /**
     * @param null|string $title
     *
     * @return $this|string
     */
    public function title($title = null)
    {
        if(func_num_args() == 0)
        {
            if($this->data() instanceof Model) {
                $group = $this->form()->getGroup();

                if($group) {
                    $field = $group . '._tr_component_name';
                } else {
                    $field = '_tr_component_name';
                }

                $name = $this->data()->getFieldValue($field);
                $this->title = $name && $this->title != $name ? $name : $this->title;
            }

            return $this->title ?? substr(strrchr(get_class($this), "\\"), 1);
        }

        $this->title = esc_attr($title);

        return $this;
    }

    /**
     * @return mixed
     */
    public function feature($name)
    {
        if($this->{$name} && $name == 'cloneable') {
            return '<a tabindex="0" class="clone tr-clone-builder-component"></a>';
        }

        if($this->{$name} && $name == 'nameable') {
            return $this->form()->text('_tr_component_name', ['placeholder' => $this->titleUnaltered()])
                ->setDefault($this->title())
                ->attrClass('tr-component-nameable-input')
                ->raw();
        }

        return '';
    }
}