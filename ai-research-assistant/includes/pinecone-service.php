<?php

class PineconeService
{
    private $pinecone_api_key;
    private $assistant_name;

    public function __construct($pinecone_api_key, $assistant_name)
    {
        $this->pinecone_api_key = $pinecone_api_key;
        $this->assistant_name = $assistant_name;
    }

    public function getPineconeFiles()
    {
        $url = "https://prod-1-data.ke.pinecone.io/assistant/files/{$this->assistant_name}";
        $headers = array(
            "Api-Key: {$this->pinecone_api_key}"
        );

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers
        ));

        $response = curl_exec($curl);
        $response_code = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);

        if ($response === false) {
            $error_message = curl_error($curl);
            error_log("Error fetching Pinecone files: {$error_message}");
            curl_close($curl);
            throw new Exception("Error fetching Pinecone files: {$error_message}");
        }
        if ($response_code !== 200) {
            $error_message = "Error fetching Pinecone files. Response code: {$response_code}";
            error_log($error_message);
            curl_close($curl);
            throw new Exception($error_message);
        }
        curl_close($curl);

        $files = json_decode($response, true);
        return $files;
    }

    public function uploadFileToPinecone($filePath, $metadata) {
        if (!file_exists($filePath)) {
            throw new RuntimeException("File not found: $filePath");
        }

        // Encode metadata as JSON and add it to query string
        $url = "https://prod-1-data.ke.pinecone.io/assistant/files/{$this->assistant_name}";
        $headers = array(
            "Api-Key: {$this->pinecone_api_key}"
        );

        if ($metadata) {
            $url .= "?metadata=" . urlencode(json_encode($metadata));
        }

        // Initialize cURL
        $curl = curl_init();
        
        // Create CURLFile object for file upload
        $cfile = new CURLFile($filePath);
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => ['file' => $cfile],
            CURLOPT_HTTPHEADER => $headers
        ]);

        // Execute request
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        if (curl_errno($curl)) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new RuntimeException("cURL Error: $error");
        }
        
        curl_close($curl);

        return [
            'status_code' => $httpCode,
            'response' => json_decode($response, true)
        ];
    }

    public function deleteFile($file_id)
    {
        $url = "https://prod-1-data.ke.pinecone.io/assistant/files/{$this->assistant_name}/{$file_id}";
        $headers = array(
            "Api-Key: {$this->pinecone_api_key}"
        );

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => "DELETE"
        ));

        $response = curl_exec($curl);
        $response_code = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);

        if ($response === false) {
            $error_message = curl_error($curl);
            error_log("Error deleting file from Pinecone: {$error_message}");
            curl_close($curl);
            throw new Exception("Error deleting file from Pinecone: {$error_message}");
        }

        if ($response_code !== 200) {
            $error_message = "Error deleting file from Pinecone. Response code: {$response_code}";
            curl_close($curl);
            throw new Exception($error_message);
        }

        curl_close($curl);
        return json_decode($response, true);
    }
}