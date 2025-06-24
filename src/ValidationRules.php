<?php
namespace Clicalmani\Routing;

trait ValidationRules
{
    public function whereNumber(string|array $params) : self
    {
        return $this->where($params, 'numeric');
    }

    public function whereInt(string|array $params) : self
    {
        return $this->where($params, 'int');
    }

    public function whereFloat(string|array $params) : self
    {
        return $this->where($params, 'float');
    }

    public function whereEnum(string|array $params, array $list = []) : self
    {
        return $this->where($params, 'enum|list:' . join(',', $list));
    }

    public function whereToken(string|array $params) : self
    {
        return $this->where($params, 'token');
    }

    public function wherePattern(string|array $params, string $pattern) : self
    {
        return $this->where($params, "regexp|pattern:$pattern");
    }

    public function guardAgainst(string $param, callable $callback) : self
    {
        $uid = uniqid('gard-');
        Memory::addGuard($uid, $param, $callback);
        return $this->where($param, 'nguard|uid:' . $uid);
    } 

    public function whereModel(string|array $params, string $model) : self
    {
        return $this->where($params, 'required|id|model:' . substr($model, strrpos($model, '\\') + 1));
    }

    public function whereIn(string|array $params, array $list) : self
    {
        return $this->where($params, 'required|in|list:' . join(',', $list));
    }
}