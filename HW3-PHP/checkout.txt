<?php
/* This php file inserts the book copy checked out by the user in the CheckedOut Table and later list out
all the checked out books.
If the user doesn't click on any checkbox, then it will just list out previously checked out books
for that member, if exists any.
*/

#mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
// Create connection
$conn = new mysqli('localhost', 'root','root','library' );

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

<?php
$to_insert = '';
//var_dump($_POST);

//if the user checkout any book copy then it is added to 'book' array, else any empty array is passed.
$books = isset($_POST['book'])?$_POST['book']:[];
$member_id = $_POST['member_id'];

foreach($books as $copyid => $should_checkout){
    $sql = "INSERT INTO CheckedOut (copyid, mid,checkoutDate, dueDate, status)
VALUES (?, ?, ?, ?, ?)";
    $query = $conn->prepare($sql);
    $status = "Holding";
    $checkoutDate = date("Y-m-d H:i:s");
    //echo $checkoutDate;
    //dueDate is 3 months after the checkoutDate
    $date = strtotime(date("Y-m-d H:i:s", strtotime($checkoutDate)) . " +3month");
    //echo $date;
    $dueDate = date("Y-m-d H:i:s",$date);
    //echo $dueDate;
    $query->bind_param('sssss', $copyid, $member_id, $checkoutDate, $dueDate, $status);

    // run query and get result
    $query->execute();
}

?>

<?php

$query = $conn->prepare("select b.bookid, bc.copyid, b.booktitle, b.category, b.author, b.publishdate, c.dueDate
from Book b  inner join BookCopy bc  inner join CheckedOut c
inner join (select copyid, max(checkoutDate) as MaxDate from CheckedOut group by copyid
)tm on c.copyid = tm.copyid and b.bookid = bc.bookid and bc.copyid = c.copyid and c.checkoutDate = tm.MaxDate 
where c.status in ('Holding','Overdue') and c.mid = ?");

$query->bind_param('s', $member_id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows > 0) {
    echo "Checked Out Books:<br><br>";
    // output data of each row
    while($row = $result->fetch_assoc()) {
        echo "Book Name: " . $row["booktitle"]. " Copy Id: " . $row["copyid"]. " Due Date: ". $row["dueDate"]."<br>";
    }
} else {
    echo '<font size="14"'." face='Arial'>"."No book currently checked out!";
}





?>
