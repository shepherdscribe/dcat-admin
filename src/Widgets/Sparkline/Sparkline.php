<?php

namespace Dcat\Admin\Widgets\Sparkline;

use Dcat\Admin\Admin;
use Dcat\Admin\Support\Helper;
use Dcat\Admin\Traits\InteractsWithApi;
use Dcat\Admin\Widgets\Widget;
use Illuminate\Support\Str;

/**
 * @see https://omnipotent.net/jquery.sparkline
 *
 * @method $this fillColor(string $color)
 * @method $this lineColor(string $color)
 * @method $this chartRangeMin(int $val)
 * @method $this chartRangeMax(int $val)
 * @method $this enableTagOptions(bool $bool)
 * @method $this tagOptionPrefix(string $val)
 * @method $this tagValuesAttribute(string $val = 'values')
 * @method $this disableHiddenCheck(string $val)
 */
class Sparkline extends Widget
{
    use InteractsWithApi;

    public static $js = [
        '@jquery.sparkline',
    ];
    public static $css = [
        '@jquery.sparkline',
    ];

    protected static $optionMethods = [
        'highlightSpotColor',
        'highlightLineColor',
        'minSpotColor',
        'maxSpotColor',
        'spotColor',
        'lineWidth',
        'spotRadius',
        'normalRangeMin',
        'drawNormalOnTop',
        'xvalues',
        'chartRangeClip',
        'chartRangeMinX',

        'barColor',
        'negBarColor',
        'zeroColor',
        'nullColor',
        'barWidth',
        'zeroAxis',
        'colorMap',

        'targetColor',
        'targetWidth',
        'rangeColors',
        'performanceColor',

        'offset',
        'borderWidth',
        'borderColor',
        'sliceColors',

        'lineColor',
        'fillColor',
        'chartRangeMin',
        'chartRangeMax',
        'enableTagOptions',
        'tagOptionPrefix',
        'tagValuesAttribute',
        'disableHiddenCheck',
    ];

    protected $id;

    protected $type = 'line';

    protected $options = ['width' => '100%'];

    protected $values = [];

    protected $combinations = [];

    public function __construct($values = [])
    {
        $this->values($values);

        $this->options['type'] = $this->type;
    }

    /**
     * 设置图表值.
     *
     * @param mixed|null $values
     *
     * @return $this|array
     */
    public function values($values = null)
    {
        if ($values === null) {
            return $this->values;
        }

        $this->values = Helper::array($values);

        return $this;
    }

    /**
     * 设置图表宽度.
     *
     * @param int $width
     *
     * @return $this
     */
    public function width($width)
    {
        $this->options['width'] = $width;

        return $this;
    }

    /**
     * 设置图表高度.
     *
     * @param int $width
     *
     * @return $this
     */
    public function height($height)
    {
        $this->options['height'] = $height;

        $this->style('height:'.$height);

        return $this;
    }

    /**
     * 组合图表.
     *
     * @param int $width
     *
     * @return $this
     */
    public function combine(self $chart)
    {
        $this->combinations[] = [$chart->values(), $chart->getOptions()];

        return $this;
    }

    /**
     * @return string
     */
    protected function addScript()
    {
        $values = json_encode($this->values);
        $options = json_encode($this->options);

        if (! $this->allowBuildRequest()) {
            return $this->script = <<<JS
$('#{$this->getId()}').sparkline($values, $options);
{$this->buildCombinationScript()};
JS;
        }

        $this->fetched(
            <<<JS
if (!response.status) {
    return Dcat.error(response.message || 'Server internal error.');
}        
var id = '{$this->getId()}', opt = $options;
opt = $.extend(opt, response.options || {});
$('#'+id).sparkline(response.values || $values, opt);
JS
        );

        return $this->script = $this->buildRequestScript();
    }

    /**
     * @return string
     */
    protected function buildCombinationScript()
    {
        $script = '';

        foreach ($this->combinations as $value) {
            $value = json_encode($value[0]);
            $options = json_encode($value[1]);

            $script .= <<<JS
$('#{$this->getId()}').sparkline($value, $options);
JS;
        }

        return $script;
    }

    /**
     * @return string
     */
    public function render()
    {
        $this->addScript();

        $this->setHtmlAttribute([
            'id' => $this->getId(),
        ]);

        return parent::render();
    }

    public function html()
    {
        return <<<HTML
<span {$this->formatHtmlAttributes()}></span>
HTML;
    }

    /**
     * 获取容器元素ID.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id ?: ($this->id = $this->generateId());
    }

    /**
     * @param string $method
     * @param array  $parameters
     *
     * @return Sparkline|Widget
     */
    public function __call($method, $parameters)
    {
        if (in_array($method, static::$optionMethods)) {
            return $this->options([$method => $parameters[0] ?? null]);
        }

        return parent::__call($method, $parameters); // TODO: Change the autogenerated stub
    }

    /**
     * @return string
     */
    protected function generateId()
    {
        return 'sparkline-'.$this->type.Str::random(8);
    }

    /**
     * @return array
     */
    public function valueResult()
    {
        return [
            'status'  => 1,
            'values'  => $this->values(),
            'options' => $this->getOptions(),
        ];
    }
}
