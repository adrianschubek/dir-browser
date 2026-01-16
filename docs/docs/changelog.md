---
sidebar_position: 1.5
---
# Changelog

All notable changes to this project will be documented in this file.

## [4.4.0] - 2026-01-16

### 🚀 Features

- Add support for PDF previews ([98d9a93](https://github.com/adrianschubek/dir-browser/commit/98d9a9343bdac87e035d956ec5d1fdb6db2d5b8b))
- Add support for markdown previews. closes #42 ([49545de](https://github.com/adrianschubek/dir-browser/commit/49545de71698971e7987e94419a311a4219ac3e6))

### ⚙️ Miscellaneous Tasks

- Remove experimental caching ([3618116](https://github.com/adrianschubek/dir-browser/commit/361811696167ddec730d8e3f090f5e4f2b17818a))
- Bump version ([c59377e](https://github.com/adrianschubek/dir-browser/commit/c59377e07cde153f4d07422ceea6674943458f12))

## [4.3.3] - 2025-12-30

### 🐛 Bug Fixes

- Initialize variables to prevent warnings on early exit ([588d5f8](https://github.com/adrianschubek/dir-browser/commit/588d5f8028e7028cbc6149a4d39200dfdbe5d79f))
- Make utpp scan paths more specific ([ef1327d](https://github.com/adrianschubek/dir-browser/commit/ef1327d41ad96216019ac7e760a91bc4c2cdf420))
- Use official APT repo for redis and nginx ([9362402](https://github.com/adrianschubek/dir-browser/commit/9362402f3481e8ce9163861500b140777f13db37))

## [4.3.2] - 2025-12-29

### 🚀 Features

- Use debian-slim instead of alpine as base image ([3b00d3e](https://github.com/adrianschubek/dir-browser/commit/3b00d3ed31be4aa28711eac1419f057087d08d0a))

### 🐛 Bug Fixes

- Don't escape forward slashes anywhere in API ([a74e977](https://github.com/adrianschubek/dir-browser/commit/a74e9775417956b3f1c8a4852e2c27e2698d65e8))
- Php warnings ([fbc7c3c](https://github.com/adrianschubek/dir-browser/commit/fbc7c3c7204677cf48c44c461467c0e4d02a829b))

### 📚 Documentation

- Clarify preview dialog ([96a3c40](https://github.com/adrianschubek/dir-browser/commit/96a3c40383e82a9dcefac921d6e2e9bf3ccd8a4e))
- Remove exp flag ([e7d58c2](https://github.com/adrianschubek/dir-browser/commit/e7d58c2cf09469f82627582090ec187f021a293c))
- Fix hash config ([1197009](https://github.com/adrianschubek/dir-browser/commit/11970099aadce1e6e72b2d948a29950a160ee940))
- Update metadata ([833dd6b](https://github.com/adrianschubek/dir-browser/commit/833dd6bc21be32b7c5f742a1c7d1b147d0a0c960))
- Update batch & pagination ([2a0e67e](https://github.com/adrianschubek/dir-browser/commit/2a0e67ebfb770c6e1a29868ed1e6b285186f16fc))
- Fix API warning ([9f4e5d7](https://github.com/adrianschubek/dir-browser/commit/9f4e5d75c61911c3e312d00dea389cd1ad279539))
- Update API ([57bf066](https://github.com/adrianschubek/dir-browser/commit/57bf0667801fc9f682f386d43687f322fbe4ecd0))
- Remove outdated images from API ([88065bd](https://github.com/adrianschubek/dir-browser/commit/88065bd9c6d5455b8c7e20409f571a10202d2aab))
- API make links clickable ([24de4d0](https://github.com/adrianschubek/dir-browser/commit/24de4d0103d49872177ad936cb314674122465ba))
- Glob expansion is now supported ([28f5939](https://github.com/adrianschubek/dir-browser/commit/28f5939560dbc9969d48452510274c826091b886))

## [4.3.1] - 2025-12-15

### 🚀 Features

- Show icon in list when folder is locked ([b4265f1](https://github.com/adrianschubek/dir-browser/commit/b4265f1a8674507226d3554967758367ef255226))
- Show path and home link on full page auth form ([238f685](https://github.com/adrianschubek/dir-browser/commit/238f68576278033312807f1b47194c567691b1f3))

### 🐛 Bug Fixes

- Complete folder download when pagination active ([f688b9f](https://github.com/adrianschubek/dir-browser/commit/f688b9fdde938bcf67b7934f4c596043a3c54a76))
- Don't show locked files in search results ([d50e52e](https://github.com/adrianschubek/dir-browser/commit/d50e52e739469e3bafd14bc8df65f60100f572e5))
- Redirect to home after logout ([2cf0721](https://github.com/adrianschubek/dir-browser/commit/2cf07215a4435f803e7f27e8726a576a5eef99ed))

### 📚 Documentation

- Fix table ([21a6d75](https://github.com/adrianschubek/dir-browser/commit/21a6d75f2d85d912283f936b8c244e00f008912a))
- Remove outdated note ([61ec2a1](https://github.com/adrianschubek/dir-browser/commit/61ec2a107fcf34aede116e086a97a8c708d01b14))

## [4.3.0] - 2025-12-15

### 🚀 Features

- Add TITLE config for custom page title ([376c081](https://github.com/adrianschubek/dir-browser/commit/376c081e6e48c21d0ca04081cf90311752f1d895))
- Add batch download streaming ([ffcd246](https://github.com/adrianschubek/dir-browser/commit/ffcd246827eee320a3def47cd1d30443db080c9d))
- Add toast notifications for batch download preparation ([80b2ad6](https://github.com/adrianschubek/dir-browser/commit/80b2ad6fffa1824997cb18001eb38ccaf850380e))

### 📚 Documentation

- Escape dollar sign for docker ([c7e1ece](https://github.com/adrianschubek/dir-browser/commit/c7e1eceb50c88f5ef76435e4edff4dbeb24e42fc))

## [4.2.0] - 2025-12-14

### 🚀 Features

- Add configurable auth cookie lifetime and httpOnly setting ([db626c1](https://github.com/adrianschubek/dir-browser/commit/db626c109a34986ab415d608ead7b8da488ea4fb))
- Add password protection checks in init script ([a51c66b](https://github.com/adrianschubek/dir-browser/commit/a51c66bce45370c128fbf04e81f9d426d4d83126))
- Logout functionality and cookie deletion ([df866e4](https://github.com/adrianschubek/dir-browser/commit/df866e4b0d97ade9f499c7c8dec096654c548c57))

### 🐛 Bug Fixes

- Enhance password protection messaging and logic in index.php ([24155b8](https://github.com/adrianschubek/dir-browser/commit/24155b8838cf96533688187c2599ee1cd340ffda))

### 📚 Documentation

- Improve docs ([b15c7b7](https://github.com/adrianschubek/dir-browser/commit/b15c7b73d4f4c5a60b5c3283907882ded201e0d1))
- Remove note about noJS ([723f3a3](https://github.com/adrianschubek/dir-browser/commit/723f3a30455775f8cf7d0bf65fa2601428d480fe))
- New cookie configs ([a99a479](https://github.com/adrianschubek/dir-browser/commit/a99a4794c5ef7681fe849936456e6b2afeacdc6f))
- Update password protection documentation to include logout instructions ([2b2bcbf](https://github.com/adrianschubek/dir-browser/commit/2b2bcbf18b1b6fea7b8ce57d49cbf3a48793ff8e))

### ⚙️ Miscellaneous Tasks

- Update version 4.2.0 ([1446f3b](https://github.com/adrianschubek/dir-browser/commit/1446f3b1f435f52a0d60db9d9130f8275dcbb8b8))
- Update commonmark ([dc270b5](https://github.com/adrianschubek/dir-browser/commit/dc270b576e2fd0f7c1efd101052bcbabe639252f))

## [4.1.0] - 2025-12-13

### 🚀 Features

- Add max file size limit for hashing functionality ([621dd96](https://github.com/adrianschubek/dir-browser/commit/621dd96391724d0567ed2f2653473b33885df0d5))
- Show generated hash and used algo ([004b02b](https://github.com/adrianschubek/dir-browser/commit/004b02bbb6060e8b64c307fb6154eeb3d9678658))
- Add audio preview ([3a992da](https://github.com/adrianschubek/dir-browser/commit/3a992daa62702e3ae960227450d2897d7087a593))
- Add folder password protection. fixes #30 ([80a543e](https://github.com/adrianschubek/dir-browser/commit/80a543e89aeaef0130ad4bd6349ce1946319ae02))

### 🐛 Bug Fixes

- Dont escape forward slashes in url field in API ([d4d6b00](https://github.com/adrianschubek/dir-browser/commit/d4d6b004b367260d0eb4f2dff1f5e997105eec51))

### 📚 Documentation

- Add warning about performance impact of hashing large files ([66e7d1e](https://github.com/adrianschubek/dir-browser/commit/66e7d1ebee7d549c9dda2886528103f90399aee9))
- Freeze v3 docs ([8536f4b](https://github.com/adrianschubek/dir-browser/commit/8536f4bf7f61ea55796efd3c888702cccc6310f3))
- Prepare v4 ([d721beb](https://github.com/adrianschubek/dir-browser/commit/d721beb3b2e8840a0773a0a1e796ede4800168a9))

## [4.0.0] - 2025-12-12

### 🚀 Features

- Add popup preview and raw streaming for media files ([617400a](https://github.com/adrianschubek/dir-browser/commit/617400ac12a5b293297cf673c91b64e7f15c1d78))
- Add copy file content and hash to clipboard ([0234403](https://github.com/adrianschubek/dir-browser/commit/02344032883af359cf8e9d4c4f8614ba38dc6c29))
- Remove old actions dropdown from file display ([ae74339](https://github.com/adrianschubek/dir-browser/commit/ae743398f2fc20fb2843954f55b3b89858ee00a7))

### 📚 Documentation

- Update layout ([be3f3b9](https://github.com/adrianschubek/dir-browser/commit/be3f3b917a0a853a3a7c4df329ca7846bea44039))
- Add v4 label ([3714f07](https://github.com/adrianschubek/dir-browser/commit/3714f0711c64c77011e285e01fddc7adb9da3e7b))

### ⚙️ Miscellaneous Tasks

- Update DIRBROWSER_VERSION to 4.0.0 and adjust layout environment variable ([2f55fda](https://github.com/adrianschubek/dir-browser/commit/2f55fdad9503d4773af94fbf10bc7070d0cadccd))
- Update Bootstrap CSS and JS to version 5.3.8 ([54e4857](https://github.com/adrianschubek/dir-browser/commit/54e4857dfbc435721349c966603d3a44005cc98b))

## [3.16.0] - 2025-12-11

### 🚀 Features

- Show clipboard copy value for file name, download count, and modified date ([57b3e5f](https://github.com/adrianschubek/dir-browser/commit/57b3e5f80ad8848db30b191eb8a3de744c11f20f))

### ⚙️ Miscellaneous Tasks

- Update GitHub Actions to use latest Docker actions ([12aff6c](https://github.com/adrianschubek/dir-browser/commit/12aff6cf160492519df2368a1ea1aad080be436b))
- Update upload-pages-artifact action ([3214bff](https://github.com/adrianschubek/dir-browser/commit/3214bff3b2d817180186301b9ff26b587a08e65c))
- Update docs action to node 22 ([2ff8e38](https://github.com/adrianschubek/dir-browser/commit/2ff8e38e71c329b9d4486b9a16c471b36b6e56db))
- Update PHP version to 8.5 and bump DIRBROWSER_VERSION to 3.16.0 ([6a99d9b](https://github.com/adrianschubek/dir-browser/commit/6a99d9b9a935a7d02ad0b5c00b9573dc5cec81bc))

## [3.15.0] - 2025-08-25

### 🚀 Features

- Persist sorting. fixes #39 ([eb175f3](https://github.com/adrianschubek/dir-browser/commit/eb175f3f65e646a0152693b5f6232f8ae1ae9b09))

## [3.14.1] - 2025-08-22

### 🐛 Bug Fixes

- Disabled download counter breaks listing. fixes #38 ([9df7b94](https://github.com/adrianschubek/dir-browser/commit/9df7b9456109c8fa82d9b9f6d99fe65eb8bbcf6a))

## [3.14.0] - 2025-06-25

### 🐛 Bug Fixes

- Escape meta description ([6e34b29](https://github.com/adrianschubek/dir-browser/commit/6e34b29ae8b5e7ef648264b0043532e1cd858538))

### ⚡ Performance

- Use redis mget for api list ([e89e288](https://github.com/adrianschubek/dir-browser/commit/e89e2881c94acfa02a0c9c8f549b7f1d9f469fa1))
- Use native filesystem iterator ([3459043](https://github.com/adrianschubek/dir-browser/commit/34590437c903531cff2854292be3a74d94d58161))

### ⚙️ Miscellaneous Tasks

- Bump version ([6d1dce0](https://github.com/adrianschubek/dir-browser/commit/6d1dce0ab2e336adb0399f5a643ecb77876abc2b))

## [3.13.0] - 2025-06-09

### 🚀 Features

- Cors allow any origin ([0e3819b](https://github.com/adrianschubek/dir-browser/commit/0e3819b37aef09663f16dca363814cfa261fd281))

### 🐛 Bug Fixes

- Cors for files ([e726dff](https://github.com/adrianschubek/dir-browser/commit/e726dff023c19b321adc363ca78def35c96f758c))

### 📚 Documentation

- Update changelog ([ae8deb3](https://github.com/adrianschubek/dir-browser/commit/ae8deb303826074ef7b1628f6340058d4a15000d))
- Cors ([993bd17](https://github.com/adrianschubek/dir-browser/commit/993bd17622cbacaf4dd834a0b3900bf27b41a6c2))

### ⚙️ Miscellaneous Tasks

- Update LICENSE date ([012f608](https://github.com/adrianschubek/dir-browser/commit/012f608713e3dc9e14acc372a74a8547f8df1f0a))
- Update commonmark ([3294438](https://github.com/adrianschubek/dir-browser/commit/32944384bfb2daa1084ee9b61e14150af9a7a34a))

## [3.12.0] - 2025-03-22

### 🚀 Features

- Toggle prefetching for files and folders ([372ef3f](https://github.com/adrianschubek/dir-browser/commit/372ef3f2e5a36b7234596ec8bb3c1fecf1d501de))

### 📚 Documentation

- Update demo run command ([521434f](https://github.com/adrianschubek/dir-browser/commit/521434fdd7e21cc07b3df6cd3e52fb99be03c515))

## [3.11.2] - 2025-03-19

### 🐛 Bug Fixes

- File info api downloads always 0 ([43877d1](https://github.com/adrianschubek/dir-browser/commit/43877d172c5d9312ffcd24f4d5a329be9200ddc5))

## [3.11.1] - 2025-02-27

### 🐛 Bug Fixes

- Readme css url ([99fd80f](https://github.com/adrianschubek/dir-browser/commit/99fd80ff7821c990a53fd1e37b3c4aaba7ae0ae2))
- Skip prefetching for pagination buttons ([ca5e73e](https://github.com/adrianschubek/dir-browser/commit/ca5e73e2571e6402a67016a18d1425c41e001553))

### ⚙️ Miscellaneous Tasks

- Bump version ([b39f2a9](https://github.com/adrianschubek/dir-browser/commit/b39f2a9e0d6da567102db065a347dfbd1cd82255))

## [3.11.0] - 2025-02-27

### 🚀 Features

- Improve performance for large folders. fixes #35 ([435de39](https://github.com/adrianschubek/dir-browser/commit/435de394a9512e50018a03bbb7f8604b978bbad4))

### 📚 Documentation

- Fix import ([a32825e](https://github.com/adrianschubek/dir-browser/commit/a32825e040a2f4a8551ca4a247473bcbc6f32865))

### ⚙️ Miscellaneous Tasks

- Update commonmark ([41ddc68](https://github.com/adrianschubek/dir-browser/commit/41ddc68924b4e146ccddd2b8e523186c35d094da))

## [3.10.0] - 2025-02-27

### 🚀 Features

- Add pagination. closes #34 ([bb53b36](https://github.com/adrianschubek/dir-browser/commit/bb53b36900ee3ffae05b7b360d17e6ca51173a6d))

### 🐛 Bug Fixes

- Nginx permissions. fixes #33 ([46323d7](https://github.com/adrianschubek/dir-browser/commit/46323d7a975898856f02a25562428f21a5fa1116))
- Workflow docs ([783f54d](https://github.com/adrianschubek/dir-browser/commit/783f54d1b300951cf930043957cc07e56003170b))
- New workflow ([342f244](https://github.com/adrianschubek/dir-browser/commit/342f2442e064ced987be937a63231ee67c1db545))
- Workflow ([7fe5fa7](https://github.com/adrianschubek/dir-browser/commit/7fe5fa7c2262cc5971237e476016111a5f505f4e))
- Workflow npm ([134e92f](https://github.com/adrianschubek/dir-browser/commit/134e92f767d24eef064be40b37aad818a720a9a8))
- Workflow cache ([6bc1e85](https://github.com/adrianschubek/dir-browser/commit/6bc1e852dc813b5b9b55e51ebb5a8aa6eb6a3d23))
- Workflow cmd ([43dd303](https://github.com/adrianschubek/dir-browser/commit/43dd303174ca23096a90a57a30aec6bf3befd1c1))
- Path ([361ca71](https://github.com/adrianschubek/dir-browser/commit/361ca71274b935bcebcca89fdf85a122ad2cb72f))

### 📚 Documentation

- Add pagination ([49fabb4](https://github.com/adrianschubek/dir-browser/commit/49fabb415c214e4af16ea32aa889f114615f7f9a))

### ⚙️ Miscellaneous Tasks

- Bump version ([9620e5b](https://github.com/adrianschubek/dir-browser/commit/9620e5bf742b8f633d95eca3741fb41aa8af4bc6))
- Fix workflow docs ([433bd54](https://github.com/adrianschubek/dir-browser/commit/433bd54f0d6a1ad1df3949b2b73c81990d0da9e5))
- Bump version ([eb9a0bc](https://github.com/adrianschubek/dir-browser/commit/eb9a0bc1b5ddadd2e446742f8e96285a44ca0e04))

## [3.9.0] - 2024-11-26

### 🚜 Refactor

- Custom init ([4dcab15](https://github.com/adrianschubek/dir-browser/commit/4dcab15cdd3c27b731c39c32ad0b944d95a058dc))

### ⚡ Performance

- Drop supervisor. use custom init ([9a4f653](https://github.com/adrianschubek/dir-browser/commit/9a4f65337100628682f721d3c9e2a755a4f7a9a4))

### ⚙️ Miscellaneous Tasks

- Upgrade to php 8.4 ([8cddaca](https://github.com/adrianschubek/dir-browser/commit/8cddacad00f044803f9ac09e0bb15b02a979d579))
- Use env version ([b29025c](https://github.com/adrianschubek/dir-browser/commit/b29025c44b73f9b0883dece0408afda668bea1e2))

## [3.8.1] - 2024-11-04

### 🐛 Bug Fixes

- Deny .dbmeta.md direct url access ([5bb93c4](https://github.com/adrianschubek/dir-browser/commit/5bb93c4b31641e1582867ce730822245862c0ae6))

### 📚 Documentation

- Update header ([1049849](https://github.com/adrianschubek/dir-browser/commit/104984911a219ac41cb027d5b2db90aadcdda3d0))

## [3.8.0] - 2024-10-31

### 🚀 Features

- Support user selection of multiple search engines ([e7f4689](https://github.com/adrianschubek/dir-browser/commit/e7f46897a20f0bd21853d60e6db31ef0a880491b))

### 🐛 Bug Fixes

- Simple search should be case insensitive ([7c9791f](https://github.com/adrianschubek/dir-browser/commit/7c9791f021eac00e2c4a51b92d1b9b47efb28192))

### 📚 Documentation

- Fix typo ([81861c5](https://github.com/adrianschubek/dir-browser/commit/81861c56942483700f57be98919b4920e3c4bf8f))
- Update search ([36b0eea](https://github.com/adrianschubek/dir-browser/commit/36b0eeabb936a8cf207aca03c6c26b24911c7165))

### ⚙️ Miscellaneous Tasks

- Update engine env ([f64c9b1](https://github.com/adrianschubek/dir-browser/commit/f64c9b16137fc0455a6a2f95f567dde27609aa3a))

## [3.7.0] - 2024-10-30

### 🚀 Features

- Add global search env config ([37066a9](https://github.com/adrianschubek/dir-browser/commit/37066a9fe41ec47cdc44c4485ba1785079e8c9d7))
- Add global search. closes #31 ([d35d7b7](https://github.com/adrianschubek/dir-browser/commit/d35d7b71423fd56872eb3cef7a8c641e7f6a5075))
- Add simple search engine ([7ca9458](https://github.com/adrianschubek/dir-browser/commit/7ca9458cb40d2549fd524813985b0d7c89cc57f9))

### 📚 Documentation

- Update description meta ([792c9bf](https://github.com/adrianschubek/dir-browser/commit/792c9bf8eb0f9523d2ef6145c0aadf2e1824a968))
- Add global search ([d1e8efa](https://github.com/adrianschubek/dir-browser/commit/d1e8efa2371db36f26924ed045cd755f4ceee1a8))
- Add simple search engine ([b7c1db4](https://github.com/adrianschubek/dir-browser/commit/b7c1db482ed40d447831186ae5f02c7245e25ce6))

### ⚙️ Miscellaneous Tasks

- Update commonmark ([8d24ef4](https://github.com/adrianschubek/dir-browser/commit/8d24ef453975fcaaa32bd34f9e6de760e4e8a3c7))
- Update preprocessor paths ([2e471ae](https://github.com/adrianschubek/dir-browser/commit/2e471ae8adb36bf93bec21318fbc21bf71a186e7))
- Set default engine ([86f9faa](https://github.com/adrianschubek/dir-browser/commit/86f9faa5f168beacd09075dd0548516431606fae))

## [3.6.1] - 2024-10-29

### 🐛 Bug Fixes

- Readme render order ([e6c5f3c](https://github.com/adrianschubek/dir-browser/commit/e6c5f3c0606bd859c99b01212e5e0d88152f967a))
- Use default zip compression ([966ad75](https://github.com/adrianschubek/dir-browser/commit/966ad75b0b6dc74f1473314f0443219a49902a38))

### 📚 Documentation

- Default zip compression ([d0038cd](https://github.com/adrianschubek/dir-browser/commit/d0038cd8622775c0a65b41655c770d6b84287b7b))

## [3.6.0] - 2024-10-29

### 🚀 Features

- Support multiple readmes ([70c3f91](https://github.com/adrianschubek/dir-browser/commit/70c3f913d96f10441faa1f53717f995728729b5c))

### 🐛 Bug Fixes

- Track batch downloads ([a0e5f6d](https://github.com/adrianschubek/dir-browser/commit/a0e5f6dd65b18c91774938e8b8d9e63029db0d44))

### 📚 Documentation

- Fix wording ([a878c2d](https://github.com/adrianschubek/dir-browser/commit/a878c2da556d6e8fa6a00c0df6da224dbc568f30))
- Batch download tracker ([a3ef7cb](https://github.com/adrianschubek/dir-browser/commit/a3ef7cb27fc4e8b6e723f08c59d0355d502eac53))
- Update batch tracking ([f36adb0](https://github.com/adrianschubek/dir-browser/commit/f36adb0be8be2af0fd9d857fc91e37ef884e23fd))

## [3.5.0] - 2024-10-29

### 🚀 Features

- Add .dbmeta.md rendering. closes #29 ([642a55f](https://github.com/adrianschubek/dir-browser/commit/642a55f2685b18bb9422175eceae45720af521f5))

### 📚 Documentation

- Add .dbmeta.md docs ([2c51345](https://github.com/adrianschubek/dir-browser/commit/2c5134570c82d79439223f023045da9bca208ba9))

### ⚙️ Miscellaneous Tasks

- Update docusaurus ([b9ec59c](https://github.com/adrianschubek/dir-browser/commit/b9ec59c3516f1a50a9613301b7d432c62aa88e51))

## [3.4.1] - 2024-10-28

### 🐛 Bug Fixes

- Dark mode file list background ([5eec341](https://github.com/adrianschubek/dir-browser/commit/5eec341c6d509c7a79ccdd89da3ff2878d41a5a0))

## [3.4.0] - 2024-10-28

### 🚀 Features

- Add github-style readme theme ([0690c84](https://github.com/adrianschubek/dir-browser/commit/0690c846de53132bab835b2b07799643c80e521c))
- Use github markdown style. fixes #28 ([2de20ac](https://github.com/adrianschubek/dir-browser/commit/2de20acfa8407d8321b65d4754e1d8ae272248fc))

### 🐛 Bug Fixes

- Gh readme style ([a5eef7b](https://github.com/adrianschubek/dir-browser/commit/a5eef7b7e4be0363f8379dd1fc99182d7dedb8ea))
- Gh readme style again ([49b90e6](https://github.com/adrianschubek/dir-browser/commit/49b90e636403465b39f0b03c332e2c93030008fa))
- Gh style ([9a2d5e8](https://github.com/adrianschubek/dir-browser/commit/9a2d5e8f99bdfcfc6c628327d2dd406dd9904fec))
- Gh readme finally ([6f72575](https://github.com/adrianschubek/dir-browser/commit/6f72575105c42570cf634991de8982f2bdbf3329))

### 📚 Documentation

- Update layout transition ([f496932](https://github.com/adrianschubek/dir-browser/commit/f49693231fcd0a7794ea02506fddc1d85e471caa))

### ⚙️ Miscellaneous Tasks

- Folders ([fc30701](https://github.com/adrianschubek/dir-browser/commit/fc307011c1ea5b46a54255e9facdf98af22613fd))


## [3.3.2] - 2024-07-20

### 🚀 Features

- Add page transitions ([a909fd3](https://github.com/adrianschubek/dir-browser/commit/a909fd3522b7fcead7f7165de9c3a8d683fa2991))
   Enable with `TRANSITION=true` (default `false`) 

### 🐛 Bug Fixes

- Turbo navigation broken, leading to full page reload ([5058f04](https://github.com/adrianschubek/dir-browser/commit/5058f04f11cd8887b273d2fe0bd7cbb0e52dda1d))

## [3.3.1] - 2024-06-21

### 🐛 Bug Fixes

- Image display too large. closes #24 ([540fe6d](https://github.com/adrianschubek/dir-browser/commit/540fe6dd861779e3571cd22fbcca9f3f56f025fe))
- Skip parent folder in api ls ([3820b3b](https://github.com/adrianschubek/dir-browser/commit/3820b3bb072a7ca8f63a667eeed1f4841a19f73e))

### 📚 Documentation

- Update batch ([ff04e95](https://github.com/adrianschubek/dir-browser/commit/ff04e95c2fbb4fff3cbefd703d3aa985d2fe1e2d))
- Update features ([68631af](https://github.com/adrianschubek/dir-browser/commit/68631aff53020b14f33f006c4531d9ec38e3825b))

## [3.3.0] - 2024-06-20

### 🚀 Features

- Add multiple ignore patterns support. closes #15 ([c73af4a](https://github.com/adrianschubek/dir-browser/commit/c73af4a9b602a96c8f0a01db1600c9dcd5db3a8e))
- Regex ignore patterns. closes #16 ([2ec12a9](https://github.com/adrianschubek/dir-browser/commit/2ec12a953ccb2e7758a43ccb0f97a5d404186888))
- Add global password ([50d252a](https://github.com/adrianschubek/dir-browser/commit/50d252a6289dc90b3754d14d04719bd0c8ce3a6f))
- Make header sticky ([db63a19](https://github.com/adrianschubek/dir-browser/commit/db63a19eb375353bd08dfa5ead5d0f30911b6745))
- Add multi-select ([ccd5bb2](https://github.com/adrianschubek/dir-browser/commit/ccd5bb27ba3aaa5194532c99437bd5df683704c6))
- Add global HASH_REQUIRED ([bb28e46](https://github.com/adrianschubek/dir-browser/commit/bb28e46f62a1c26b0be2a1065c1789990ce4b833))
- Option to disable metadata completely ([efdde42](https://github.com/adrianschubek/dir-browser/commit/efdde42266f37bc00815e6593eb08487382d2c48))
- Add API folder listing link ([d537b86](https://github.com/adrianschubek/dir-browser/commit/d537b860b0d46b663ad73ddd558c5f7eddf4c73b))
- Add custom css and js. closes #22 ([0edc7a2](https://github.com/adrianschubek/dir-browser/commit/0edc7a271787beaf35d041d0b164c848cebf99a7))
- Add multiple select ([c3960ad](https://github.com/adrianschubek/dir-browser/commit/c3960ad26046e6afaa0d4a619e52098475331c73))
- Add batch download. closes #12 ([d45c196](https://github.com/adrianschubek/dir-browser/commit/d45c19613bb954e36e1cce91fe35b60881e34f5c))
- Add file info dropdown menu. closes #11 ([87a0e0b](https://github.com/adrianschubek/dir-browser/commit/87a0e0b12a55ef8e4ee199292d03f330c5ca7219))

### 🐛 Bug Fixes

- Update not found UI ([1d03355](https://github.com/adrianschubek/dir-browser/commit/1d03355feb142dddcabd58f42c8698a6845cf49c))
- Ignore pattern condition check ([d37c351](https://github.com/adrianschubek/dir-browser/commit/d37c35124114303a47fc54e7f6e14c7b7354cf13))
- Use multibyte strtolower ([1da1725](https://github.com/adrianschubek/dir-browser/commit/1da1725eaf26227e0ae29a4c234aee46268aedab))
- White flash in darkmode ([9b9ea56](https://github.com/adrianschubek/dir-browser/commit/9b9ea569010bf66b48f20ed02a745a2b833381cc))
- Open new tab in readme. closes #21 ([48b9cfb](https://github.com/adrianschubek/dir-browser/commit/48b9cfb84e30bf4c928506b52a77d6e6eb329e25))
- Relative links broken in readme. closes #20 ([d7c1966](https://github.com/adrianschubek/dir-browser/commit/d7c196683ab741bc265356b854734530fd75cc5f))
- Path navbar slash missing. related #20 ([7e85d99](https://github.com/adrianschubek/dir-browser/commit/7e85d995ee4f9c6bb5b78f84d3162cad954ee7a8))
- Scrollbar content shifting ([f901dd8](https://github.com/adrianschubek/dir-browser/commit/f901dd8eb1e1a20b1b7b5edcdf162fbfd5095dd5))
- Plus sign in file name results in not found error. fixes #23 ([141dc39](https://github.com/adrianschubek/dir-browser/commit/141dc3940c867eb7d6590669d642152b4722735b))

### 📚 Documentation

- Add redis guide ([3e6997b](https://github.com/adrianschubek/dir-browser/commit/3e6997b64113496d4761be99f09ed6eedaf517fb))
- Fix ignore tip ([b111c3a](https://github.com/adrianschubek/dir-browser/commit/b111c3a5e550c0c12592d64bde5a9d24e2e3f81f))
- Cleanup ignore and password ([aee1202](https://github.com/adrianschubek/dir-browser/commit/aee1202f4cc82d53dfa9b5d8f84b195384148f85))
- Improve documentation for ignore patterns and metadata ([5997f85](https://github.com/adrianschubek/dir-browser/commit/5997f85e00815ca5a08b83cf3c07e76f57cc9e65))
- Update themes custom url ([b4fe508](https://github.com/adrianschubek/dir-browser/commit/b4fe508421bd7e7dfd46f1b9dd72be8afb84b4a8))
- Fix title case ([4c37da6](https://github.com/adrianschubek/dir-browser/commit/4c37da6f9c59701ee3eda4853043cfba94c186bb))
- Update command ([253249f](https://github.com/adrianschubek/dir-browser/commit/253249f71cc4cb3ad2ecbc4695471b566506191c))
- Add api disclaimer ([9b3d257](https://github.com/adrianschubek/dir-browser/commit/9b3d257e708c7c5b1e10db222b6c2b6b367c7bb2))

### ⚙️ Miscellaneous Tasks

- Add API dropdown check ([46ad429](https://github.com/adrianschubek/dir-browser/commit/46ad429c86efa7ef01f936e3dde22b80edff7288))

## [3.2.0] - 2024-06-15

### 🚀 Features

- Add readme on top option. closes #10 ([abd2d38](https://github.com/adrianschubek/dir-browser/commit/abd2d38b0d90d43bf443cffd91ecfb9475efd981))
- Add open file in new tab. closes #13 ([c70ad53](https://github.com/adrianschubek/dir-browser/commit/c70ad53087abfa797f65173b429a07364b8fe16b))

### 📚 Documentation

- Move timing ([7458585](https://github.com/adrianschubek/dir-browser/commit/74585858bd9c9468bd9b913f970fd5593507f053))
- Update features ([9b3f8f3](https://github.com/adrianschubek/dir-browser/commit/9b3f8f3a1b1755694d113b39927b49ed6e70f2ef))
- Update header ([c5eaf0a](https://github.com/adrianschubek/dir-browser/commit/c5eaf0aef7491ce9b92b2311b2b1419669ed655f))
- Update design ([a8ba50c](https://github.com/adrianschubek/dir-browser/commit/a8ba50ceaf3bb0c5accca9b1f6e977620cea5649))
- Add config details ([b3051aa](https://github.com/adrianschubek/dir-browser/commit/b3051aa7e9d78692495696e18ae26569c6351e3b))
- Update envconfig ([3b08962](https://github.com/adrianschubek/dir-browser/commit/3b08962c67bdacc728bb033bfc6b598d8402a0d4))
- Fix envconfig ([707aedc](https://github.com/adrianschubek/dir-browser/commit/707aedc73f37b4e0918f27a17c81aa0db82b7f28))

## [3.1.0] - 2024-06-04

### 🚀 Features

- Add password_hash ([7318a6c](https://github.com/adrianschubek/dir-browser/commit/7318a6cee33e77a5d70c48988b7b787de54ae1f7))

### 📚 Documentation

- Update v3 whats new ([12ad1ab](https://github.com/adrianschubek/dir-browser/commit/12ad1ab46a810ad58a535324146b39cc43fea87b))
- Add v3 content ([8b9e6da](https://github.com/adrianschubek/dir-browser/commit/8b9e6da8d424eabfaeaf7327369da7acbe53b472))
- Fix v3 metadata url ([e79edb8](https://github.com/adrianschubek/dir-browser/commit/e79edb837e9eda5fc9d518c29586d71bd835347b))
- Update ignore ([e78c330](https://github.com/adrianschubek/dir-browser/commit/e78c3304a4d449e8cddbc63f9b752793aa78d45a))

### ⚙️ Miscellaneous Tasks

- Update image paths and styles ([4bc7acd](https://github.com/adrianschubek/dir-browser/commit/4bc7acdefbe63d65034b05925ed6e93514e1510b))
- Update environment variables, add HASH_ALGO, fix API ([dbabb7b](https://github.com/adrianschubek/dir-browser/commit/dbabb7b6460e63218c486b32d009eb64f49ffcb2))
- Update examples ([7ba1902](https://github.com/adrianschubek/dir-browser/commit/7ba1902a3f34910d841fbacd41313b39a4459151))

## [3.0.0] - 2024-05-29

### 🚀 Features

- Add request timing stats ([7f89914](https://github.com/adrianschubek/dir-browser/commit/7f89914824dce210eb0dc8540b150d9605ba56f5))
- Make relative format default ([93c9e90](https://github.com/adrianschubek/dir-browser/commit/93c9e906f15d967555c1287b1c70575a121cff70))
- New design and caching ([f0e03aa](https://github.com/adrianschubek/dir-browser/commit/f0e03aa5520ead46cc157b279d4446f5528d21f7))
- Clickable navbar path ([d89dd7a](https://github.com/adrianschubek/dir-browser/commit/d89dd7a3790d024372402c194e0a569388eead3c))
- Improve style and add tooltips ([91a7494](https://github.com/adrianschubek/dir-browser/commit/91a749472b9ebf6aec2fe8188dff9db41969d402))
- Add has and info apis ([461cf16](https://github.com/adrianschubek/dir-browser/commit/461cf16b8fa10770a5a38b75ad20996d75047aa7))
- Improve UI column design ([5c8c6e6](https://github.com/adrianschubek/dir-browser/commit/5c8c6e60d6b6a1957babb16a39730c487c1b7f79))
- Remove navbar. use modern UI ([ff5ee4d](https://github.com/adrianschubek/dir-browser/commit/ff5ee4dce81414576bd2c026bab449e080e032aa))
- Add folder list json api ([d5b737f](https://github.com/adrianschubek/dir-browser/commit/d5b737fb2c38bffa64261f21b519384f48d6aed9))
- Add file tree sorting ([721b38c](https://github.com/adrianschubek/dir-browser/commit/721b38cfa325b283980fb322f4bc6a64e4929916))
- Add search ([76cfdc8](https://github.com/adrianschubek/dir-browser/commit/76cfdc8200d5557c7bcc3f552e8ff86eb240711d))

### 🐛 Bug Fixes

- Hidden file condition ([32fd241](https://github.com/adrianschubek/dir-browser/commit/32fd2415d46b48abd6b7930b0d9e0fb7639704ac))

### 📚 Documentation

- Fix ignore example ([ecd7b19](https://github.com/adrianschubek/dir-browser/commit/ecd7b1997cd1e2a526a2b931f632188405c93937))
- Update features ([e210523](https://github.com/adrianschubek/dir-browser/commit/e210523bd80984d6b678b19dbbccc82c81dcb02d))
- Prepare for v3 ([f614da4](https://github.com/adrianschubek/dir-browser/commit/f614da4ae01aa293e1e9c722225b1df4c07609d8))
- Fix broken links v3 ([807c359](https://github.com/adrianschubek/dir-browser/commit/807c3590ef8590f4142cd1948c3e8af754520784))
- Fix broken link v3 ([92afd6f](https://github.com/adrianschubek/dir-browser/commit/92afd6f80e4c79c23b409477c5d64441c824f846))
- Fix broken link v3 ([c762575](https://github.com/adrianschubek/dir-browser/commit/c762575774fc2726188d7a1a890e83f9a60c84ad))
- V3 docs init ([b957df9](https://github.com/adrianschubek/dir-browser/commit/b957df987d55d3bf7f641957fadf884ebef10ebd))

### ⚙️ Miscellaneous Tasks

- Add examples ([630217a](https://github.com/adrianschubek/dir-browser/commit/630217a2a0b512651bb6c46ea10e7cd841a7d211))
- Readme ([d2e1e42](https://github.com/adrianschubek/dir-browser/commit/d2e1e421deaf7ddaebdb6881167af2a900827dbc))

### Feaet

- Add header to file tree ([44eae92](https://github.com/adrianschubek/dir-browser/commit/44eae92e671f4a03e22268632110c4dc0cc93726))

## [2.5.0] - 2024-05-21

### 🚀 Features

- Add hidden metadata option ([6ce2134](https://github.com/adrianschubek/dir-browser/commit/6ce2134d1e77681feb2069cac274858e788cefee))

### 📚 Documentation

- Update docker run install ([5e4350b](https://github.com/adrianschubek/dir-browser/commit/5e4350beb9b56424c6b3463754faf2a73d5d96f0))
- Update docker run on landingpage ([b1ebabc](https://github.com/adrianschubek/dir-browser/commit/b1ebabced4b890f446db4ada462a23f35b768250))

## [2.4.1] - 2024-04-25

### 🐛 Bug Fixes

- Metadata incorrectly loaded for additional files ([c4b446a](https://github.com/adrianschubek/dir-browser/commit/c4b446ae8b20dc96007a5d5e2acbdb1dad7eb4fa))

### 📚 Documentation

- Update demo server url ([14570d7](https://github.com/adrianschubek/dir-browser/commit/14570d72084d923bc46a8c607916bfb702ad9287))

## [2.4.0] - 2024-04-25

### 🚀 Features

- Add password proterction for files ([a64df1d](https://github.com/adrianschubek/dir-browser/commit/a64df1d04e349bdd85d8bd439cb23f376f7d6ab2))
- Password protection, docs updated ([908067b](https://github.com/adrianschubek/dir-browser/commit/908067b36dd77009ea62c104aae18c93329416d9))

### 🐛 Bug Fixes

- Allow any querystring ([467c9b5](https://github.com/adrianschubek/dir-browser/commit/467c9b537b4a6f1d612df4ece18b4e945acc1b41))

### 📚 Documentation

- Update performance ([2d1af61](https://github.com/adrianschubek/dir-browser/commit/2d1af61334f59a645949f10dc1c0ca0924740cf5))

### ⚙️ Miscellaneous Tasks

- Update bootstrap 5.3.3 and turbo 8.0.4 ([7111185](https://github.com/adrianschubek/dir-browser/commit/7111185b8befd52d36c362bb3d11cf24f80f8a95))

## [2.3.1] - 2024-04-24

### 🐛 Bug Fixes

- Inverted allow_raw_html check in readme renderer ([bf795bd](https://github.com/adrianschubek/dir-browser/commit/bf795bda0b926ca41f02320f8677776c07ea1b50))

### 📚 Documentation

- Clarify metadata ([d38f1fb](https://github.com/adrianschubek/dir-browser/commit/d38f1fbc3696d38cda03557e02af841f24e320ef))
- Update homepage ([0c9ee68](https://github.com/adrianschubek/dir-browser/commit/0c9ee68bd77846debd45a81892e6741cacc80a71))
- Update readme ([db84385](https://github.com/adrianschubek/dir-browser/commit/db8438575f3b910a987ea2241f54c4367bd02851))

## [2.3.0] - 2024-04-23

### 🚀 Features

- Add metadata, custom description and labels ([dbe33cb](https://github.com/adrianschubek/dir-browser/commit/dbe33cb953fc8556399f565404b331978bba26e0))

### ⚙️ Miscellaneous Tasks

- Bump php to 8.3 ([0e9dc5a](https://github.com/adrianschubek/dir-browser/commit/0e9dc5a9360afb340ecca5cce52903461fde5d4b))

## [2.2.1] - 2024-04-23

### 🐛 Bug Fixes

- Caching issue #7 ([6361e42](https://github.com/adrianschubek/dir-browser/commit/6361e421835e3dc6a39f51913fd69cdf75694071))

### 📚 Documentation

- Update features ([6d89272](https://github.com/adrianschubek/dir-browser/commit/6d8927280f84a8494ca5b2f85bb1750524d6d326))

### ⚙️ Miscellaneous Tasks

- Bump version ([d969ee0](https://github.com/adrianschubek/dir-browser/commit/d969ee0240f78a40724c7eb5b4487c6bfcdc61b9))

## [2.0.1] - 2024-01-12

### 📚 Documentation

- Update relative time format ([47d8a53](https://github.com/adrianschubek/dir-browser/commit/47d8a5380d255c7d77c66cb91c02ac0e42040913))

## [2.0.0] - 2024-01-12

### 📚 Documentation

- Update disclaimer password ([548261d](https://github.com/adrianschubek/dir-browser/commit/548261d9a3266316837f684d73fb3dc68ac75de5))
- Update download count ([7cf56b3](https://github.com/adrianschubek/dir-browser/commit/7cf56b32089d7956125a9b0792bb085af3193030))
- Fix readme config ([5797771](https://github.com/adrianschubek/dir-browser/commit/5797771d4ab292e7971956c406b1ff1dbe05e4dd))
- Add highlight updated ([6081065](https://github.com/adrianschubek/dir-browser/commit/6081065ce6b857f10fd665940d664d6e0618f786))
- Add new options ([ecfc899](https://github.com/adrianschubek/dir-browser/commit/ecfc899e86b2fe8e3ac258c87f1770ad2f38c2ad))
- Update title ([3a12154](https://github.com/adrianschubek/dir-browser/commit/3a12154e7e632995f95762abf5636dd3460d3f61))
- Update version ([ba07469](https://github.com/adrianschubek/dir-browser/commit/ba074695ae68f84572cb7178724fa963d54bdd43))

## [1.4.0] - 2024-01-11

### 📚 Documentation

- Update roadmap ([befa368](https://github.com/adrianschubek/dir-browser/commit/befa3689cea873c62d1075bb805287c6d11d38d3))
- Update dev ([8b34a9e](https://github.com/adrianschubek/dir-browser/commit/8b34a9e939ab793374cb0378f885d2fe3077e3c4))
- Fix typo in reverse proxy ([a7ab68a](https://github.com/adrianschubek/dir-browser/commit/a7ab68aa992997b1652141d99a47291f14832696))
- Improve development docs ([cf35641](https://github.com/adrianschubek/dir-browser/commit/cf356412b567ade8dd8ae1bb48a461618d4190cd))

## [1.3.3] - 2023-11-02

### 📚 Documentation

- Update basepath ([8c55ff8](https://github.com/adrianschubek/dir-browser/commit/8c55ff86e8f248cd119cbb757aeb1e9bb67ab7b7))

### ⚙️ Miscellaneous Tasks

- Update gh action ([d8da94d](https://github.com/adrianschubek/dir-browser/commit/d8da94defde8bb600467fce07ec73e692d8523a8))
- Update version ([90508f0](https://github.com/adrianschubek/dir-browser/commit/90508f0cbff293e41cbc43791c864e024cc5f6ff))

## [1.3.2] - 2023-10-31

### 📚 Documentation

- Update subpath ([0bcbaf9](https://github.com/adrianschubek/dir-browser/commit/0bcbaf91c7bb255fa25d2448609a195ad1cc9747))

### ⚙️ Miscellaneous Tasks

- Update version ([b9e7d40](https://github.com/adrianschubek/dir-browser/commit/b9e7d40e5c1702c175b8d78ab38514d74cb65513))

## [1.3.1] - 2023-10-31

### 📚 Documentation

- Update demo docker run cmd ([7429778](https://github.com/adrianschubek/dir-browser/commit/7429778cca25b5dfe70b3362d879d5029e476900))
- Update date format link ([3bc6c96](https://github.com/adrianschubek/dir-browser/commit/3bc6c961e0d1d0a94e6687b796a113c38d02b44e))

## [1.3.0] - 2023-05-29

### 🚀 Features

- Add themes ([7406cee](https://github.com/adrianschubek/dir-browser/commit/7406ceeeb231d9a2e8587d646a8dc0aceaa13d20))

### 📚 Documentation

- Readme update ([8a9fc42](https://github.com/adrianschubek/dir-browser/commit/8a9fc42e52feaef475dc4d81501235ff8775c552))
- Update sidebar design ([3f886b4](https://github.com/adrianschubek/dir-browser/commit/3f886b4a45e766792f054f2148d8a0a60171885f))
- Update roadmap ([4a6d4a6](https://github.com/adrianschubek/dir-browser/commit/4a6d4a622d860a212f8231830bd7c6e76bd890c9))
- Update menu styling ([a12e1b9](https://github.com/adrianschubek/dir-browser/commit/a12e1b9b472df592d94ca7e85adfbd2794bb22f1))
- Update dark gh icon ([c7856d5](https://github.com/adrianschubek/dir-browser/commit/c7856d54d05ad421c9acee55a5ed8ba12f833948))
- Add search ([79fe535](https://github.com/adrianschubek/dir-browser/commit/79fe5353520186de14e7cc925b1fd48957481ed8))
- Fix site title ([48d4e4d](https://github.com/adrianschubek/dir-browser/commit/48d4e4da369e95d2a5abe5c1e7525d9a4e5583ce))

## [1.2.0] - 2023-04-28

### 📚 Documentation

- Update features ([2898724](https://github.com/adrianschubek/dir-browser/commit/2898724817883c6d442b91b3f3b9512bd43e4b1b))
- Update features ([eb9197a](https://github.com/adrianschubek/dir-browser/commit/eb9197adf00391cf01566bb80787d777268567a3))
- Update install ([64bea22](https://github.com/adrianschubek/dir-browser/commit/64bea2294a8396581b04a1f3b8a73012e6e3d7dc))
- Add performance guide ([7debd60](https://github.com/adrianschubek/dir-browser/commit/7debd60528b213a9d1d12817d8f1fe1a26c751ab))
- Add homepage ([78d4072](https://github.com/adrianschubek/dir-browser/commit/78d40728d3b329b083d38a5a63907a220ad6e84f))
- Readme ([fc33ded](https://github.com/adrianschubek/dir-browser/commit/fc33ded15be7f9c4c06f193c15cb74ec625021f2))
- Add quickstart ([576c62b](https://github.com/adrianschubek/dir-browser/commit/576c62b7a7607ad68d0804ae217e7628b43fc31b))
- Improve ([a5f4529](https://github.com/adrianschubek/dir-browser/commit/a5f4529e5897a38449eaf3894d25b42203060651))
- New design ([af36f70](https://github.com/adrianschubek/dir-browser/commit/af36f704a19cc70bf013c56dc3c7ecb483d8a680))

## [1.1.0] - 2023-04-20

### 🚀 Features

- Add readme rendering ([9077c9b](https://github.com/adrianschubek/dir-browser/commit/9077c9b1454e4919c13b6270ceb0e997e7ff4d94))

### 📚 Documentation

- Update strucutre ([fdb1b8d](https://github.com/adrianschubek/dir-browser/commit/fdb1b8dde71830d4e9de46cdd3f237113b912fa5))
- Readme fix ([c47993e](https://github.com/adrianschubek/dir-browser/commit/c47993e8f826551ff20394f81c63c5b4df4da3a3))
- Update 1.x ([303a28b](https://github.com/adrianschubek/dir-browser/commit/303a28b73937962da21f0cde7ed47c0a38a9237d))
- Update 1.x ([39e204d](https://github.com/adrianschubek/dir-browser/commit/39e204d2dbc1d479d4227b197f2c50897bb3a215))

## [1.0.0] - 2023-04-05

### 📚 Documentation

- V1.0 ([7225a3e](https://github.com/adrianschubek/dir-browser/commit/7225a3e74b1f6e8993199b7defe4be7438161722))