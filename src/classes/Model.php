<?php
abstract class Model {
    abstract public function create();
    abstract public function read();
    abstract public function update();
    abstract public function delete();
}