<?php
namespace App\Support\Connect;

class VariableDefinition
{
    public string $id;
    public string $label;
    public string $description;
    public string $example;
    public string $category;

    public function __construct(string $id, string $label, string $description, string $example, string $category)
    {
        $this->id = $id;
        $this->label = $label;
        $this->description = $description;
        $this->example = $example;
        $this->category = $category;
    }
}
