# Encrypted Chat
A military-grade protection chat (256-bit encryption + RSA-4096) created by Giacomo Casadei
The system uses an asymmetric encryption system to guarantee the privacy of messages without limiting their length.
You can also send any type of file in the chat.

Created with `PHP`

# Cryptographic Algorithms Used
AES-256-GCM
end-to-end RSA-4096
bcrypt (for password hashing)

# Steps in encrypting a message
1) A 32 bytes (64 hex characters) key are generated with `random_bytes` php function.
   Using random_bytes in PHP is crucial for generating cryptographically secure bytes because it ensures unpredictability and uniform distribution.
   This is vital for cryptographic applications like key generation, session tokens, or random passwords, enhancing overall security.

2) The text message (or file) is encrypted utilizing the Advanced Encryption Standard (AES) algorithm in its 256-bit Galois/Counter Mode (GCM) configuration.
   The encryption key employed is the one generated as specified in point 1, aligning with high-level security standards to ensure data integrity and authenticity.
   This methodology leverages the GCM mode for robust protection against replay attacks, providing a highly secure and efficient symmetric encryption scheme.

3) The key generated in step 1 is encrypted with both the recipient's and the sender's public key and both are saved in the chat database as message properties.

# Steps in decrypting a message
1) From the message instance the key encrypted with my public key is selected I decrypt with my private key
2) The encrypted text of the message (or file) is decrypted with the obtained key
