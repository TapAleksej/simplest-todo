<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todo Application</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .task-form { margin-bottom: 20px; }
        .task-list { list-style-type: none; padding: 0; }
        .task-item { padding: 10px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; }
        .task-item.completed { text-decoration: line-through; color: #888; }
        .btn { padding: 5px 10px; cursor: pointer; }
        .btn-delete { background-color: #ff4444; color: white; border: none; }
        .btn-complete { background-color: #4CAF50; color: white; border: none; }
    </style>
</head>
<body>
    <h1>Todo Application</h1>
    
    <div class="task-form">
        <h2>Add New Task</h2>
        <form method="POST" action="">
            <input type="text" name="title" placeholder="Task title" required style="padding: 8px; width: 300px;">
            <textarea name="description" placeholder="Task description" style="padding: 8px; width: 300px; margin-top: 5px;"></textarea>
            <button type="submit" name="add_task" style="padding: 8px 15px; margin-top: 5px;">Add Task</button>
        </form>
    </div>

    <h2>Tasks</h2>
    <ul class="task-list">
        <?php
        // Database connection
        $servername = "localhost";
        $username = "todo_user";
        $password = "TodoPassword123";
        $dbname = "todo_db";
        
        // Create connection
        $conn = new mysqli($servername, $username, $password, $dbname);
        
        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        
        // Add new task
        if (isset($_POST['add_task'])) {
            $title = $_POST['title'];
            $description = $_POST['description'];
            
            $stmt = $conn->prepare("INSERT INTO tasks (title, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $title, $description);
            $stmt->execute();
            $stmt->close();
            
            // Refresh to show the new task
            header("Location: ".$_SERVER['PHP_SELF']);
            exit();
        }
        
        // Delete task
        if (isset($_GET['delete'])) {
            $id = $_GET['delete'];
            $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            
            // Refresh to remove the task
            header("Location: ".$_SERVER['PHP_SELF']);
            exit();
        }
        
        // Update task status
        if (isset($_GET['complete'])) {
            $id = $_GET['complete'];
            $stmt = $conn->prepare("UPDATE tasks SET status = 'completed' WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            
            // Refresh to update the task
            header("Location: ".$_SERVER['PHP_SELF']);
            exit();
        }
        
        // Display tasks
        $sql = "SELECT * FROM tasks ORDER BY created_at DESC";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $status_class = $row['status'] == 'completed' ? 'completed' : '';
                echo "<li class='task-item $status_class'>";
                echo "<div>";
                echo "<strong>" . htmlspecialchars($row['title']) . "</strong>";
                echo "<p>" . htmlspecialchars($row['description']) . "</p>";
                echo "<small>Created: " . $row['created_at'] . "</small>";
                echo "</div>";
                echo "<div>";
                if ($row['status'] != 'completed') {
                    echo "<a href='?complete=" . $row['id'] . "' class='btn btn-complete'>Complete</a> ";
                }
                echo "<a href='?delete=" . $row['id'] . "' class='btn btn-delete' onclick='return confirm(\"Are you sure?\")'>Delete</a>";
                echo "</div>";
                echo "</li>";
            }
        } else {
            echo "<li>No tasks found</li>";
        }
        
        $conn->close();
        ?>
    </ul>
</body>
</html>
