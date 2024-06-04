# Integrity & Hashes

It is possible to verify the integrity of a file by setting the `?hash` query parameter to the hash of the file. If the hash does not match the actual hash of the file, an error will be returned instead.

The [file info API](http.mdx) will also include the hash of the file.

`sha256` is used as the default hashing algorithm but can be changed to any of the [supported](https://www.php.net/manual/en/function.hash-algos.php) algorithms.

#### Example

https://dir-demo.adriansoftware.de/Dockerfile?hash=foobar123

will return an access denied error.

Setting it to the correct hash will return the file as usual.

https://dir-demo.adriansoftware.de/Dockerfile?hash=f5fdab1cae965ab1adf812c84058d582defb98750fefe49ad16cd24ed051038f

You can also use a POST request to verify the hash:

```bash
curl -X POST https://dir-demo.adriansoftware.de/Dockerfile -d "hash=f5fdab1cae965ab1adf812c84058d582defb98750fefe49ad16cd24ed051038f"
```

### Mandatory hashes

Set in the [metadata](metadata.md) config of the file to require the hash to be set. If the hash is not set, an error will be returned.

```json title="<file>.dbmeta.json"
{
  "hash_required": true
}
```

:::warning
This feature should not be used to restrict access as the hash is publicly available through the [API](http.mdx). It is only meant to make an integrity verification of the file mandatory for every request.

To protect the file use the [password protection](password.mdx) feature.
:::

import EnvConfig from '@site/src/components/EnvConfig';

<!-- <EnvConfig name="HASH" init="true" values="true,false"/> -->
<EnvConfig name="HASH|HASH_ALGO" init="true|sha256" values="true,false|md2,md4,md5,sha1,sha224,sha256,sha384,sha512/224,sha512/256,sha512,sha3-224,sha3-256,sha3-384,sha3-512,ripemd128,ripemd160,ripemd256,ripemd320,whirlpool,snefru,snefru256,gost,gost-crypto,adler32,crc32,crc32b,crc32c,fnv132,fnv1a32,fnv164,fnv1a64,joaat,murmur3a,murmur3c,murmur3f,xxh32,xxh64,xxh3,xxh128"/>
