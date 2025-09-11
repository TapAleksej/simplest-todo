<?php
use PHPUnit\Framework\TestCase;

class TodoAppTest extends TestCase
{
    protected $mysqli;

    protected function setUp(): void
    {
        // Создается мок объект mysqli
        $this->mysqli = $this->createMock(mysqli::class);
    }

    public function testAddTask()
    {
        $stmtMock = $this->getMockBuilder(stdClass::class)->setMethods(['bind_param', 'execute', 'close'])->getMock();
        $stmtMock->expects($this->once())->method('bind_param')->with('ss', 'Задача', 'Описание');
        $stmtMock->expects($this->once())->method('execute')->willReturn(true);
        $stmtMock->expects($this->once())->method('close');

        $this->mysqli->method('prepare')->willReturn($stmtMock);

        $_POST['add_task'] = true;
        $_POST['title'] = 'Задача';
        $_POST['description'] = 'Описание';

        ob_start();
        include 'todo.php'; // Имя файла с Вашим кодом
        ob_end_clean();

        // Assertions тут могут быть только на факты выполнения нужных методов.
        // header/location не проверяется в юнит-тестах.
    }

    public function testDeleteTask()
    {
        $stmtMock = $this->getMockBuilder(stdClass::class)->setMethods(['bind_param', 'execute', 'close'])->getMock();
        $stmtMock->expects($this->once())->method('bind_param')->with('i', 1);
        $stmtMock->expects($this->once())->method('execute')->willReturn(true);
        $stmtMock->expects($this->once())->method('close');

        $this->mysqli->method('prepare')->willReturn($stmtMock);

        $_GET['delete'] = 1;

        ob_start();
        include 'todo.php';
        ob_end_clean();
    }

    public function testCompleteTask()
    {
        $stmtMock = $this->getMockBuilder(stdClass::class)->setMethods(['bind_param', 'execute', 'close'])->getMock();
        $stmtMock->expects($this->once())->method('bind_param')->with('i', 2);
        $stmtMock->expects($this->once())->method('execute')->willReturn(true);
        $stmtMock->expects($this->once())->method('close');

        $this->mysqli->method('prepare')->willReturn($stmtMock);

        $_GET['complete'] = 2;

        ob_start();
        include 'todo.php';
        ob_end_clean();
    }
}
