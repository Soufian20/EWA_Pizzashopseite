<?php
session_start();
require_once './Page.php';

class Bestellung extends Page
{
    protected function __construct()
    {
        parent::__construct();
    }

    protected function __destruct()
    {
        parent::__destruct();
    }

    protected function getViewData()
    {

        $sql = "SELECT * FROM Angebot";


        $recordset = $this->database->query($sql);
        $numRows = mysqli_num_rows($recordset);
        if (!$recordset)
            throw new Exception("Abfrage fehlgeschlagen: " . $this->database->error);

        // read selected records into result array

        /* 	for ($count = 0; $count < $numRows; $count++){
            $row = mysqli_fetch_array($recordset, MYSQLI_ASSOC);
            //echo json_encode($row);   
            //print_r($row) ; 
        } */

        $pizza = $recordset->fetch_all(MYSQLI_ASSOC);

        /* 		while($row = $recordset->fetch_assoc()) {
            echo $row["PizzaNummer"]. "<br>";
            echo $row["PizzaName"]. "<br>";
            echo $row["Bilddatei"]. "<br>";
            echo $row["Preis"]. "<br>";
        } */

        $recordset->free();
        return $pizza;
    }



    protected function generateView()
    {
        $pizza = $this->getViewData();
        $sql = "SELECT BestellungID FROM Bestellung WHERE BestellungID=(SELECT max(BestellungID) FROM Bestellung);";
        $recordset = $this->database->query($sql);
        $pizza23 = $recordset->fetch_all(MYSQLI_ASSOC);
        //print_r($pizza23);
        //echo $pizza[1]["PizzaName"];
        $numOfRecords = count($pizza);
        $this->generatePageHeader('Bestellung');
        ?>



    <!-- SPEISEKARTE -->
    <section class="Speisekarte">
        <h2>Speisekarte</h2>


        <?php
        for ($i = 0; $i < count($pizza); $i++) {
            // echo $pizza[$i]["PizzaName"];
            //{$pizza[$i]["PizzaName"]}
            ?>
            <div style="cursor: pointer;" onclick="zumWarenkorb(<?php echo ($i + 1); ?>)">
                <span class="gericht, block">
                    <?php echo $pizza[$i]["PizzaNummer"] ?>.
                    <span id="pizza<?php echo ($i + 1); ?>"><?php echo $pizza[$i]["PizzaName"] ?></span>
                    €
                    <span id="price<?php echo ($i + 1); ?>" data-price="<?php echo $pizza[$i]["Preis"]  ?>">
                        <?php echo $pizza[$i]["Preis"]  ?>
                    </span>
                </span>
                <img alt="<?php echo $pizza[$i]["PizzaName"] ?>" width="250" height="150" src="<?php echo $pizza[$i]["Bilddatei"] ?>">
            </div>

        <?php } ?>




    </section>

    
    <!-- WARENKORB -->
    <section class="Warenkorb" id="warenkorb">
        <h2>Warenkorb</h2>
        <h3 id="pizzaanzahl">Sie haben <span id="anzahlpizza">0</span>  Pizzen ausgewählt</h3>
        <ul id="liste"></ul>
    </section>

    
    <section class="Formular">
        <h2>Details</h2>
        <label for="Gesamtpreis">Gesamtpreis:
            <output id="Gesamtpreis">0.00€</output>
        </label>
        <fieldset>
            <span>Bitte machen Sie Ihre Eingaben</span> <br>
            <label>
                <span>Vorname:</span> <br>
                <input type="text" id="Vorname" name="Vorname" value="" placeholder="Ihr Vorname" maxlength="15" required />
            </label>
            <br>
            <label>
                <span>Nachname:</span> <br>
                <input type="text" id="Nachname" name="Nachname" value="" placeholder="Ihr Nachname" maxlength="15" required />
            </label>
            <br>
            <label>
                <span>Adresse:</span> <br>
                <input type="text" id="Adresse" name="Adresse" value="" placeholder="Ihre Adresse" maxlength="15" required /> <!-- required: Feld darf nicht leer bleiben-->
            </label>
            <br>
            <button id="deleteContact" onclick="deleteContact()">Kontaktdaten zurücksetzen</button>
            <button id="deleteCart" onclick="deleteCart()">Warenkorb leeren</button>
            <button id="button123" onclick="pushToDB()">Absenden</button>

        </fieldset>
        
    </section>






    <?php
    $this->generatePageFooter();
}



protected function processReceivedData()
{
    parent::processReceivedData();
    if (isset($_POST["bestellen_btn"])) {
        $bestelltePizzen = $_POST['Bestellungen'];
        //print_r($bestelltePizzen);

        $Vorname = mysqli_real_escape_string($this->database, $_POST['Vorname']);
        $Nachname = mysqli_real_escape_string($this->database, $_POST['Nachname']);
        $Adresse = mysqli_real_escape_string($this->database, $_POST['Adresse']);
        $created_date = date("Y-m-d H:i:s");
        $sql = "INSERT INTO `Bestellung` (`BestellungsID`, `Adresse`, `Vorname`, `Nachname`, `Bestellzeitpunkt`) VALUES (NULL, '$Adresse', '$Vorname','$Nachname','$created_date');";
        mysqli_query($this->database, $sql);
        for ($i = 0; $i < count($bestelltePizzen); $i++) {
            $sql = "SELECT BestellungsID FROM Bestellung WHERE Bestellzeitpunkt='$created_date' union SELECT PizzaNummer FROM Angebot WHERE PizzaName='$bestelltePizzen[$i]';";
            $recordset = $this->database->query($sql);
            $pizza = $recordset->fetch_all(MYSQLI_ASSOC);
            $fbestnummer = $pizza[0]['BestellungsID'];
            $fpizzanummer = $pizza[1]['BestellungsID'];
            $sqli = "INSERT INTO `BestelltePizza` (`PizzaID`, `fBestellungsID`, `fPizzaNummer`, `Status`) VALUES (NULL, '$fbestnummer', '$fpizzanummer', 'Bestellung eingegangen');";
            mysqli_query($this->database, $sqli);
            $sql = "SELECT BestellungsID FROM Bestellung WHERE BestellungsID=(SELECT max(BestellungsID) FROM Bestellung);";
            $recordset = $this->database->query($sql);
            $pizza23 = $recordset->fetch_all(MYSQLI_ASSOC);
            $_SESSION['BestellungsID'] = $pizza23[0]['BestellungsID'];
            echo ('Die Sessionvariable laute:' . $_SESSION['BestellungsID']);
        }


        //$this->database->query($sql);
        //print_r($Adresse);
    }
}

public static function main()
{
    try {
        $page = new Bestellung();
        $page->processReceivedData();
        $page->generateView();
    } catch (Exception $e) {
        header("Content-type: text/plain; charset=UTF-8");
        echo $e->getMessage();
    }
}
}

Bestellung::main();
?>