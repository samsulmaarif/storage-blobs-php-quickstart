<?php
require_once 'vendor/autoload.php';
require_once "./random_string.php";
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use WindowsAzure\Common\ServicesBuilder;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;
use MicrosoftAzure\Storage\Blob\Models\CreateContainerOptions;
use MicrosoftAzure\Storage\Blob\Models\PublicAccessType;
//$connectionString = "DefaultEndpointsProtocol=https;AccountName=rahmatmacdstorage;AccountKey=P/70SyFmLWI6yUcxjLGiaIODQqiV8dmWT960y37EK9U+BRV0GPDDGGp0mqbR3VEKKYbNYQLFRW2XGFHCecOciQ==";
$connectionString = "DefaultEndpointsProtocol=https;AccountName=dicodingsamsul;AccountKey=o1STvtAc/3j1gqKOBfTRw/tR9C+2/0/b/DI9uJRuwacKWJKHJXgG1BsuPuYHdLXS3beqURTqLsCtrd81NK3uzg==;EndpointSuffix=core.windows.net";
// Create blob client.
$blobClient = BlobRestProxy::createBlobService($connectionString);
$fileToUpload = null;
$containerName = "img".generateRandomString();
// die($containerName);

if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
	// die(var_dump($_FILES));
	$fileToUpload = $_FILES['image']['tmp_name'];
	$fileName = $_FILES['image']['name'];
	define('CHUNK_SIZE', 1024*1024);
	// die(var_dump($fileToUpload));
	if (!isset($_GET["Cleanup"])) {
    // Create container options object.
    $createContainerOptions = new CreateContainerOptions();
    // Set public access policy. Possible values are
    // PublicAccessType::CONTAINER_AND_BLOBS and PublicAccessType::BLOBS_ONLY.
    // CONTAINER_AND_BLOBS:
    // Specifies full public read access for container and blob data.
    // proxys can enumerate blobs within the container via anonymous
    // request, but cannot enumerate containers within the storage account.
    //
    // BLOBS_ONLY:
    // Specifies public read access for blobs. Blob data within this
    // container can be read via anonymous request, but container data is not
    // available. proxys cannot enumerate blobs within the container via
    // anonymous request.
    // If this value is not specified in the request, container data is
    // private to the account owner.
    $createContainerOptions->setPublicAccess(PublicAccessType::CONTAINER_AND_BLOBS);
    // Set container metadata.
    $createContainerOptions->addMetaData("key1", "value1");
    $createContainerOptions->addMetaData("key2", "value2");
    try {
        // Create container.
        $blobClient->createContainer($containerName, $createContainerOptions);
        // Getting local file so that we can upload it to Azure
        // $myfile = fopen($fileToUpload, "w") or die("Unable to open file!");
        // fclose($myfile);
        
        // # Upload file as a block blob
        // echo "Uploading BlockBlob: ".PHP_EOL;
        // echo $fileToUpload;
        // echo "<br />";
        
        $content = fopen($fileToUpload, "r");
        $data = fread($content, CHUNK_SIZE);
        //Upload blob
        $blobClient->createBlockBlob($containerName, $fileName, $data);
        // List blobs.
        $listBlobsOptions = new ListBlobsOptions();
        // $listBlobsOptions->setPrefix("HelloWorld");
        do{
            $result = $blobClient->listBlobs($containerName, $listBlobsOptions);
            foreach ($result->getBlobs() as $blob)
            {
                // echo $blob->getName().": ".$blob->getUrl()."<br />";
                $message = "These are the blobs present in the container: <br/>"
                						.$blob->getUrl()
                						." (copy this link and Analyze)<br/><br/>";
            }
        
            $listBlobsOptions->setContinuationToken($result->getContinuationToken());
        } while($result->getContinuationToken());
        // echo "<br />";
        // Get blob.
        // echo "This is the content of the blob uploaded: ";
        // $blob = $blobClient->getBlob($containerName, $fileToUpload);
        // fpassthru($blob->getContentStream());
        // echo "<br />";
    }
    catch(ServiceException $e){
        // Handle exception based on error codes and messages.
        // Error codes and messages are here:
        // http://msdn.microsoft.com/library/azure/dd179439.aspx
        $code = $e->getCode();
        $error_message = $e->getMessage();
        echo $code.": ".$error_message."<br />";
    }
    catch(InvalidArgumentTypeException $e){
        // Handle exception based on error codes and messages.
        // Error codes and messages are here:
        // http://msdn.microsoft.com/library/azure/dd179439.aspx
        $code = $e->getCode();
        $error_message = $e->getMessage();
        echo $code.": ".$error_message."<br />";
    }
	} 
	else 
	{
	    try{
	        // Delete container.
	        echo "Deleting Container".PHP_EOL;
	        echo $_GET["containerName"].PHP_EOL;
	        echo "<br />";
	        $blobClient->deleteContainer($_GET["containerName"]);
	    }
	    catch(ServiceException $e){
	        // Handle exception based on error codes and messages.
	        // Error codes and messages are here:
	        // http://msdn.microsoft.com/library/azure/dd179439.aspx
	        $code = $e->getCode();
	        $error_message = $e->getMessage();
	        echo $code.": ".$error_message."<br />";
	    }
	}
}
?>

<!DOCTYPE html>
<html>
<head lang="en">
    <title>Analyze Sample</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js"></script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
</head>
<body>

<div class="container">

<h1>Analyze image:</h1>
Upload Image
<form class="form-inline" action="" enctype="multipart/form-data" id="formUpload" method="POST">
<div class="form-group">
	<input class="form-control" type="file" name="image" id="image"/>
	<button class="btn btn-primary" type="submit" id="submit">Upload</button>
</div>
</form>
<br><br>
Enter the URL to an image, then click the <strong>Analyze image</strong> button.
<br><br>
<?php
if (isset($message))
	echo $message;
?>
Image to analyze:
<input type="text" name="inputImage" id="inputImage" />
<button class="btn btn-primary" onclick="processImage()">Analyze Image</button>
<div id="wrapper" style="width:1020px; display:table;">
    <div id="jsonOutput" style="width:600px; display:table-cell;">
        Response:
        <br><br>
        <textarea id="responseTextArea" class="UIInput"
                  style="width:580px; height:400px;"></textarea>
    </div>
    <div id="imageDiv" style="width:420px; display:table-cell;">
        Source image:
        <br><br>
        <img id="sourceImage" width="400" />
        <p id="captions"></p>
    </div>
</div>

</div>

<script>
  function processImage() {
  		// $("#formUpload").submit();
      // **********************************************
      // *** Update or verify the following values. ***
      // **********************************************

      // Replace <Subscription Key> with your valid subscription key.
      var subscriptionKey = "94e1ddddbb3e439ba95d5094ef847bdc";

      // You must use the same Azure region in your REST API method as you used to
      // get your subscription keys. For example, if you got your subscription keys
      // from the West US region, replace "westcentralus" in the URL
      // below with "westus".
      //
      // Free trial subscription keys are generated in the "westus" region.
      // If you use a free trial subscription key, you shouldn't need to change
      // this region.
      var uriBase =
          "https://dicoodingfinalcvsamsul.cognitiveservices.azure.com/vision/v2.0/analyze";

      // Request parameters.
      var params = {
          "visualFeatures": "Categories,Description,Color",
          "details": "",
          "language": "en",
      };

      // Display the image.
      var sourceImageUrl = document.getElementById("inputImage").value;
      document.querySelector("#sourceImage").src = sourceImageUrl;

      // Make the REST API call.
      $.ajax({
          url: uriBase + "?" + $.param(params),

          // Request headers.
          beforeSend: function(xhrObj){
              xhrObj.setRequestHeader("Content-Type","application/json");
              xhrObj.setRequestHeader(
                  "Ocp-Apim-Subscription-Key", subscriptionKey);
          },

          type: "POST",

          // Request body.
          data: '{"url": ' + '"' + sourceImageUrl + '"}',
      })

      .done(function(data) {
          // Show formatted JSON on webpage.
          $("#responseTextArea").val(JSON.stringify(data, null, 2));
          $("#captions").html(data.description.captions[0].text);
      })

      .fail(function(jqXHR, textStatus, errorThrown) {
          // Display error message.
          var errorString = (errorThrown === "") ? "Error. " :
              errorThrown + " (" + jqXHR.status + "): ";
          errorString += (jqXHR.responseText === "") ? "" :
              jQuery.parseJSON(jqXHR.responseText).message;
          alert(errorString);
      });
  };
</script>
</body>
</html>
