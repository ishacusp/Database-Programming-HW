
<?php

/* This php file takes the member id inputted by the user on the htm page
as member_id and the search keyword to list out all the available books */


//mysqli_report(MYSQLI_REPORT_ALL);

// Create connection
$conn = new mysqli('localhost', 'root','root','library' );
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$is_authorized = false;

if($member_id = $_REQUEST['member_id']) {
    // prepare statement (to avoid sql injection)
    $query = $conn->prepare("SELECT * FROM Member WHERE mid = ?");
    $query->bind_param('s', $member_id);

    // run query and get result
    $query->execute();
    $res = $query->get_result();
    $row = $res->fetch_assoc();

    // if the input member is valid, then save the input in member_id and change the value of $is_authorized to true
    if($member_id == $row['mid']){
        $is_authorized = true;
    }
}

?>

<html>
<head></head>
<body>

<div id='results'>
    <?php
    //check if the input member id exists in the library system or not
    if(!$is_authorized){
        ?> <h1>Sorry, not authorized to enter the library system:/</h1> <?php
    }
    else {
        // prepare statement (to avoid sql injection)
        //this query checks the status of book
        /*
        Two cases exists:

        Case 1: If the copy of the particular book has been previously checked out and thus exists in CheckedOut Table,
        with status = 'Returned' and thus is currently available.
        Case 2: The copy of the book was never checked out before, and thus is currently available.
        */

        $query = $conn->prepare("select bs.* from (
select b.*, sd.status, bc.copyid from Book b 
inner join BookCopy bc on b.bookid = bc.bookid
left join (
  select c.* from CheckedOut c 
  inner join (
    select copyid, mid, max(checkoutDate) as maxDate 
    from CheckedOut group by copyid
  ) tm on c.copyid = tm.copyid and c.checkoutDate = tm.maxDate
) sd on bc.copyid = sd.copyid
where sd.status = 'Returned' or sd.status is NULL)bs
where bs.booktitle like ? or bs.category like ?
");

        $q = '%'.($_POST['search']?:'').'%';
        $query->bind_param('ss', $q, $q);

        // run query and get result
        $query->execute();
        $result = $query->get_result();

        ?>
        <form action="checkout.php" method="post">
        <?php
        if ($result->num_rows > 0) {


            while ($book = $result->fetch_assoc()) {
                ?>
                <input type="checkbox" name='book[<?= $book['copyid'] ?>]'>
                <h3><?= $book['bookid'] ?>
                    <?= $book['booktitle'] ?>
                </h3>

                <p><?= $book['category'] ?>
                    <?= $book['copyid'] ?>
                    <?= $book['author'] ?>
                    <?= $book['publishdate'] ?>


                </p>
                <?php
            }



            ?>
        <input type='hidden' name='member_id' value='<?= $member_id ?>'>

        <input type="submit">
        </form><?php

        }

        else {
        //if all the books are already checked out.
            echo '<font size="14"'." face='Arial'>". "OOPS! All the books are already checked out.";
        }


    }

    ?>
</div>
</body>
</html>

