## SSH Key Authentication

Key authentication makes connecting to remote servers easy and secure.
To enable key authentication you need to set the paths to the files containing the keys. 

Use the following parameters to set the keys of the deployment server:

* __dbud.ssh.key.public__:  
Path to the public key
* __dbud.ssh.key.private__:  
Path to the private key
* __dbud.ssh.key.passphrase__:  
Passphrase for the private key file
    
## Queue Worker

To handle the tasks which take some time, D-Buddy uses a queue.
A queue requires a worker to be handle the queued jobs. 

You can start the queue worker for D-Buddy with the following command:    
    
    php application/console.php queue worker dbud <sleep-time-in-seconds>