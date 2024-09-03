<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* modules/contrib/restui/templates/restui-resource-info.html.twig */
class __TwigTemplate_484d0a708d44370a2d8b5eba03d89122 extends Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
        $this->sandbox = $this->env->getExtension(SandboxExtension::class);
        $this->checkSecurity();
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 15
        if ((($context["granularity"] ?? null) == "resource")) {
            // line 16
            yield "    <p>methods: ";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, Twig\Extension\CoreExtension::join($this->sandbox->ensureToStringAllowed(CoreExtension::getAttribute($this->env, $this->source, ($context["configuration"] ?? null), "methods", [], "any", false, false, true, 16), 16, $this->source), ", "), "html", null, true);
            yield "<br>
        formats: ";
            // line 17
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, Twig\Extension\CoreExtension::join($this->sandbox->ensureToStringAllowed(CoreExtension::getAttribute($this->env, $this->source, ($context["configuration"] ?? null), "formats", [], "any", false, false, true, 17), 17, $this->source), ", "), "html", null, true);
            yield "<br>
        authentication: ";
            // line 18
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, Twig\Extension\CoreExtension::join($this->sandbox->ensureToStringAllowed(CoreExtension::getAttribute($this->env, $this->source, ($context["configuration"] ?? null), "authentication", [], "any", false, false, true, 18), 18, $this->source), ", "), "html", null, true);
            yield "
    </p>
";
        } else {
            // line 21
            yield "    ";
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(($context["configuration"] ?? null));
            foreach ($context['_seq'] as $context["method"] => $context["properties"]) {
                // line 22
                yield "        <p>";
                yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($context["method"], 22, $this->source), "html", null, true);
                yield "<br>
            formats: ";
                // line 23
                yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, Twig\Extension\CoreExtension::join($this->sandbox->ensureToStringAllowed(CoreExtension::getAttribute($this->env, $this->source, $context["properties"], "supported_formats", [], "any", false, false, true, 23), 23, $this->source), ", "), "html", null, true);
                yield "<br>
            authentication: ";
                // line 24
                yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, Twig\Extension\CoreExtension::join($this->sandbox->ensureToStringAllowed(CoreExtension::getAttribute($this->env, $this->source, $context["properties"], "supported_auth", [], "any", false, false, true, 24), 24, $this->source), ", "), "html", null, true);
                yield "
        </p>
    ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['method'], $context['properties'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
        }
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["granularity", "configuration"]);        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "modules/contrib/restui/templates/restui-resource-info.html.twig";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable()
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo()
    {
        return array (  71 => 24,  67 => 23,  62 => 22,  57 => 21,  51 => 18,  47 => 17,  42 => 16,  40 => 15,);
    }

    public function getSourceContext()
    {
        return new Source("", "modules/contrib/restui/templates/restui-resource-info.html.twig", "/home/shreyarai0308/assign2/web/modules/contrib/restui/templates/restui-resource-info.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = array("if" => 15, "for" => 21);
        static $filters = array("escape" => 16, "join" => 16);
        static $functions = array();

        try {
            $this->sandbox->checkSecurity(
                ['if', 'for'],
                ['escape', 'join'],
                [],
                $this->source
            );
        } catch (SecurityError $e) {
            $e->setSourceContext($this->source);

            if ($e instanceof SecurityNotAllowedTagError && isset($tags[$e->getTagName()])) {
                $e->setTemplateLine($tags[$e->getTagName()]);
            } elseif ($e instanceof SecurityNotAllowedFilterError && isset($filters[$e->getFilterName()])) {
                $e->setTemplateLine($filters[$e->getFilterName()]);
            } elseif ($e instanceof SecurityNotAllowedFunctionError && isset($functions[$e->getFunctionName()])) {
                $e->setTemplateLine($functions[$e->getFunctionName()]);
            }

            throw $e;
        }

    }
}
