<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Rules\PixKeyRule;
use Closure;

class PixKeyValidationTest extends TestCase
{
    protected function getClosure()
    {
        return function ($message) {
            throw new \Exception($message);
        };
    }

    public function test_valid_cpf_passes()
    {
        $rule = new PixKeyRule('cpf');
        // valid CPF 
        $this->expectNotToPerformAssertions();
        $rule->validate('pix_key', '52998224725', $this->getClosure());
        $rule->validate('pix_key', '529.982.247-25', $this->getClosure());
    }

    public function test_invalid_cpf_fails()
    {
        $rule = new PixKeyRule('cpf');
        $this->expectException(\Exception::class);
        $rule->validate('pix_key', '11111111111', $this->getClosure());
    }

    public function test_invalid_cpf_algorithm_fails()
    {
        $rule = new PixKeyRule('cpf');
        $this->expectException(\Exception::class);
        $rule->validate('pix_key', '52998224726', $this->getClosure());
    }

    public function test_valid_cnpj_passes()
    {
        $rule = new PixKeyRule('cnpj');
        // Valid CNPJ: 11.222.333/0001-81
        $this->expectNotToPerformAssertions();
        $rule->validate('pix_key', '11222333000181', $this->getClosure());
        $rule->validate('pix_key', '11.222.333/0001-81', $this->getClosure());
    }

    public function test_invalid_cnpj_fails()
    {
        $rule = new PixKeyRule('cnpj');
        $this->expectException(\Exception::class);
        $rule->validate('pix_key', '11222333000182', $this->getClosure());
    }

    public function test_valid_phone_passes()
    {
        $rule = new PixKeyRule('phone');
        $this->expectNotToPerformAssertions();
        $rule->validate('pix_key', '+55 (11) 99999-9999', $this->getClosure());
        $rule->validate('pix_key', '5511999999999', $this->getClosure());
    }

    public function test_invalid_phone_fails()
    {
        $rule = new PixKeyRule('phone');
        $this->expectException(\Exception::class);
        $rule->validate('pix_key', '+55 (11) 999-99', $this->getClosure());
    }

    public function test_valid_email_passes()
    {
        $rule = new PixKeyRule('email');
        $this->expectNotToPerformAssertions();
        $rule->validate('pix_key', 'test@test.com', $this->getClosure());
    }

    public function test_invalid_email_fails()
    {
        $rule = new PixKeyRule('email');
        $this->expectException(\Exception::class);
        $rule->validate('pix_key', 'test@test', $this->getClosure());
    }

    public function test_valid_random_passes()
    {
        $rule = new PixKeyRule('random');
        $this->expectNotToPerformAssertions();
        $rule->validate('pix_key', '123e4567-e89b-12d3-a456-426614174000', $this->getClosure());
    }

    public function test_invalid_random_fails()
    {
        $rule = new PixKeyRule('random');
        $this->expectException(\Exception::class);
        $rule->validate('pix_key', '123e4567-e89b-12d3-a456', $this->getClosure());
    }
}
