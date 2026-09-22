============================================================
279 - MOBILE v1.0.0 PERFORMANCE OPTIMIZATION READ-ONLY BASELINE AUDIT - BATCH62
============================================================
DATE=Fri Aug 28 11:44:26 CEST 2026
PROJECT=/home/icaffeco/ald1n-project
PURPOSE=MEASURE_REAL_MOBILE_AND_SUPPORTING_CMS_PERFORMANCE_BASELINE_BEFORE_ANY_OPTIMIZATION_PATCH
SOURCE_MUTATION=REPORT_ONLY
DATABASE_WRITES=0
DATABASE_SCHEMA_CHANGES=0
MIGRATIONS_RUN=NO
EAS_BUILD_COMMANDS_RUN=0
BUILD14_CREATED=NO
OPTIMIZATION_PATCHES_APPLIED=0

============================================================
0. CLEAN + STABLE AUTHORITY PREFLIGHT
============================================================
REPORT278_AUTHORITY=PASS_SHA256_b8298991f206811c96fc0492c7ed346d02d594e607c3740a8dea5ad288337b6d
SOURCE_AUTHORITY=PASS_HEAD_REMOTE_TREES_HTACCESS
./CLEAN-STABLE-MANIFEST.txt: OK
./CLEAN-STABLE-PRESERVE-PATHS.txt: OK
./README-RESTORE.txt: OK
./android/Ald1n-CMS-v1.0.0-production-vc13-97859c47-1199-4a82-b782-00d28f6f98c1.aab: OK
./cms-runtime/20260828-112345-manual-f8aad0/database.sql.gz: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/document-assets/pdf-logo-65102506-42a9-4852-8ef8-e928040bb7ab.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/document-assets/pdf-logo-698d4d6b-0824-4efb-bd69-62f9635884a7.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/1/legacy-1-832df612bfb788d5.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/1/legacy-2-8caacf03ce94b3e7.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/1/legacy-3-e895124e339bb7a5.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/1/legacy-4-adc4a1cc7d57a8fc.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/1/legacy-5-54dc31edadadd9d5.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/1/legacy-6-11eff2622e8cf8a3.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/1/legacy-7-ed64840eed147d31.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/1/legacy-8-b6563a2ea4af0d97.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/10/6qJ5mgKhC3vv69176csURl9KZtgYhYAchK0CgldQ.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/10/Dgbn42dWzi7a3zTXJYn2miuKTXxHszbsRf2wzj1Z.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/10/IcUmNU3NN91OxlxrpVOUQ3dR3jhw3wCNoxhDMxqZ.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/10/M90O3ooNo35KiXItpskiyNbyhP4P7E5QeY9UGHHi.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/10/nxa1jbYruZUziNaxvmPt8MUuch7sBOBQhofYcciq.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/10/pvlMr4bMeXhRmxxWOjkHbmXZMPa1a1Yr7geKiyPO.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/10/tgVTPOJOUcNFJjdClGEu8dvyidznwQUO8ze6IpZu.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/11/0EMtV8fWtEI0uxEvn5RoeUJfkECrV0JtZV1WeC8M.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/11/1hCcZw0C3qhX0chf0FByFTKZbshdXdJoArrUOHYK.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/11/D9OX4yiVRYpr9oB38o3XO7ix9Jk0AHyxH53WQuvk.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/11/DlAcUOoTauACtexZBYOfTlQqFiQM5S4hrCIkPGEt.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/11/jgqOpRMaFbvNNBkrrRRPsePCPFZa9gXaMfBCLEYN.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/11/k54qVpLXLEj0WpZrTl57DmMJM8rWpB3T2bWx5V1s.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/12/0PqUQ4PAsmII72gp2bszNKX5KS1vUUq9AtuNBivX.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/12/VH7PDkL9ATNo4LqmgLKiOiIosLfIcsashrPFJYs0.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/12/l7ZRLiksZHhtdDfEziOldFLcNKOlNUVLL1HO3qas.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/12/x0eZml1X8WcWZ5AnI7Uq0Sv1srZFmhFuBhhguBUm.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/13/9emia6XOTQ6wFrovmAuN4fVeokvhY3HoFP8bIFMA.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/13/L6DlKvSdmtqbZ0JHtPZZIDHDbxCdYlurs4D4401v.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/13/QFjHOykiVVmRlOE1eHP2XYBiZU85v26acQpbNF9q.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/13/dVjI0haaId6BH6ViTA5nQkZB8uYHVfN9UTfKvDJ1.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/13/hIqGFLuXWjSmOcAzUxm0pgMU4r4Y9OjSz6GMv8W5.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/13/xRhx458MHMgoOx0ZTpFuQrG9O0KPh2O3HV9rX796.png: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/14/0zXVI8yaZZSWTBDChL06Wn5V0nGBifHlgnRvVAjX.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/14/2PXKtG9QWO6opp8lhqbTemMnbAP5O6F2e0EpGqyj.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/14/B3lonqPD0OueBIUoO5HUepA9KxWdQ21vOrHvgc3J.png: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/14/VnLxx3pPdskeN5IpBVhL3n5EYmlHohYvcLCiya9u.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/14/iJcgda6krZIu2YKVg2vr13z3Ez4v6ie7nk7aumjn.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/14/mKK6g5H2gFjORLHbVRLdAMzoOkaRDeXxOCee765M.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/14/oBIRNxwymgCoS1ESWd3286m2EjcvsVqL7jY211ZD.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/14/vrxPTk7DvIzpB2Igupr99E3CotVhu5MKAk4iGWJO.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/15/FpIrmVl3cwICpPhHCT2GzPUnzksMI9w6u02gBEeV.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/15/SpOocu6R52Eenv51DY5exanntKj3O5lskArWtRE6.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/15/t8rlztxFT8nVj2tbGVMxCczh1QxKXckIfzSL4ReD.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/16/HyhXAv8UvoUNqXNm6HEcfX5INvzqSvgXB1HKUHhX.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/16/Iw8XYYJ5miQVUBPPNkmmM4icenbiBXsPSnrvAdxC.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/16/WEaRKZTM17cw2CEkxeHSyVI5S9YxDhzw3Rzixgzj.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/16/pYLtuMxXdfICZ4RqVdWE4TGQADopar0egrLkA4tB.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/16/rOZ86CCqun3ZaM2KSGxOIPMOn9SErRd0lTzEDU39.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/16/rVkW9920xWtpaF1V5eNZ6dUWgqX7W5BAwLJg4ztG.png: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/16/tGCvklncdlrCKsU8VzNq1jsrUGvOI4uOqPftehh4.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/16/vvX4XL3mrBWM9SroZNEMbKysr6IkLpoba3KVrBho.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/17/N71lOdeC0efSGVhXSYujNLYheNmzLEOYgK9GvDBp.png: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/17/b4zKYWzuZ39lBGzgkh0Nsvh68rzwxCF8VzEybGW3.png: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/18/711RBDBZFBLx9QWwuMx46UDZTn4iLI7x1Nftjcgi.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/18/BmMibE6BT49UqOJRqGBbIZwtDVvBlkjo4sqIM8fq.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/18/JSjf1Zj3hZudNvQNpX2shaHvyD9XQ7Aqvp7Abofa.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/18/iOnc6f6PHMdzbe1TeQ7ssAd3mwV9XOBoJVSnr9XW.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/18/yUZy7uNbIeKD5SlST6E2zrJN5cK97YNC4h0dpPLj.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/18/zsIsKK5G3i38JdJbZP3SUEgdIfICkrRTjiJyH8gG.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/2/legacy-10-e737eac7d96c6067.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/2/legacy-11-ef540c901b6595d5.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/2/legacy-12-87bbb0b475833a91.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/2/legacy-13-e1561bb9f8962a28.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/2/legacy-14-2b15816c9208b4cf.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/2/legacy-15-7f43aa0d7c39c6c9.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/2/legacy-16-6a9c37fa61a4798d.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/2/legacy-17-a2c48d63f1cd8015.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/2/legacy-18-76ff236a3d36915e.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/2/legacy-19-096aa8ae6eaa3e7c.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/2/legacy-9-6cbf74ca1fde6698.png: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/21/H7rD9Jb0uk63tB2da0UxN9HfYIzbdR42yC1uaaW9.png: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/21/t0aoGbEzA7kTE4rsjETqjlmFQI0K2zBH1nxt5fog.png: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/22/5zr5j72J6H15Lgzh7tAbyEdW3px5uO14dIomniGg.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/22/AvCMTBI7Y9sYjorAZ2M3gjkldp8Q0XFfCaGn2bi1.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/22/GPHmXMfRIjA3SfLfqlZSsFP9HgMaQOecDQ7XBWWk.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/22/JFYHqKkJVHZstnjVaPOPu1Mu0zR1JslE24I8vvjE.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/22/PmWQuICBaE6LmtqPvgKbyWuCw6GddTcJEjEWOeVH.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/22/Suf3qr355SgeK4IhJuh6O1NRDVjlvHxEVfx3WTSC.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/22/dLaZmEkN1stl8dBJ4qDyJxhGIM1vpKMDtidH2Iab.png: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/22/dhqGMslOddVvoOtH8U4gAnYnxVAHmIFtO10weBTh.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/22/iSnWn0o1nviodSi0VngErXc1fbMa2uximXlTz46Y.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/22/lF6XJXVivGQTF3NOpFg0DlMzutADZYfVuidY9rj5.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/22/n2Yw6eMjaEcDTwfqb9iZXr0wwNvTj12OIipBPG93.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/22/oU3Vm0ZDsYB9I7EuEHbKW4ZEt4MEOdqHnOvfRjnb.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/22/u5LyGMR59NMFQlfAwOMBN7mrOl7EZYhe4WpYSTlm.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/22/vSS6t2yBtncuVoulfCccFmKv9OsibIvBVplyZbMK.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/23/7dbJ6aJUzmiTsolwUBickt34rdmLofhnBz5TnAGq.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/23/99m5ZGe3WRw88jkclhQZobu5bdP1MMEo2HLP4bRo.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/23/9cdrTfBZCkh5iVb0zkAJWq25bLtM3Mf1c0DOTrjl.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/23/Fk2jOIUiMnXI7d0BShrG4DK0dXv2NYMyqsxg5uzn.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/23/UdtSYNyBX8qZkIkZXGyI5viojB8O5khPPxEoYijA.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/23/VOXxxtCDlaXyfRAcA3r5OV1F7wZI8oOUWgXcQqqT.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/23/VuoxibIvTWedqnRPZRldEktJ9gHbmE18NKiWe18w.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/23/b5FDmadpoh0GhoaezVL5MtO6rgVr7guSFg9xDW9N.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/23/tiU00aXrMBVCs2HQMmfwoe2a9PbqgLO7FxAhBNIT.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/23/ygpJKeIr7wsEVOLdsRExB1r8yKi8NYiEokm7i08i.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/24/4dyzliKBjNVdQ8QyCMHRACT7YvAY4SzSf1EYaj49.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/24/4rBLJMGlsUM3E74HeW6JfzgTTghqavAY1zv6tD1S.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/24/Ew1bJGB45rgFRyu7vmOIfN419wH10TuvLuQxSKce.png: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/24/FtOeDFUDxEpuXFMhyaXre8IYuefBOGEmXYMxvAN3.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/24/Mvb5IQjF5seswEoZhmC56Iro1rhKFCDNsoHvji4A.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/24/NcO1X0swx3kosfQWWnhzqAWzM8Zfe7TQ2du2YRJH.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/24/SLtKD55RwRvdfG4Ke3LIR7ZkEILtuGqdDVDsia0e.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/24/WymI5LDeDKKHgB4VDYy7OezbZNcqYswp7vVbFeVN.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/24/ZGoN4khgI05yub9VBka7FfCg3H1d69GqYVfXImad.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/24/mdi78CRLsIxrGhTt9TpWoiVpd2qr6zjorCsOSZwB.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/25/AWHHJ4Lpv1jiuDI3OJEii348RvoPaVSbo0GI2Gfn.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/25/HZEPKCnvnl6MlqRAbafMUNFfhiETso6MDwFVLox5.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/25/MEXY27mbdpjPGaon5ssfB0erPTDO8grpurzjMfQk.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/25/X5JvFRNNNj6n2RT1cq2c3SOr5C5ybMjoPafPHDhv.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/25/gPHi8G6uDAOtdIjzog6fEUCZRhCFCqqZKeM4yHac.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/25/nl2kmGuxL81nNgjuNc8oKHPKSMv7UnHrvIcSSM1d.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/25/st1wpcQM0nQ7PgoA3ypKQKxOjAKoXKGVzXXZx2lA.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/25/xKNq5TDml1JjXRI8buPb9X9z1MjaFgmrwui8MG5b.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/26/DVZtmJ6Tkt9fntDttwFWtyNAztkwz6ajO7JRYwEQ.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/26/J3j786VTVNffnUeuuQw0gTunv3RoeO47wWPUjLZJ.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/26/JIjo1mGytnT8P6A1bLUWhRmVc7NJTrZ4OAjYbs3h.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/26/NlTnBhlonObmu6pGDdaJIOEucuIgurZKFPP1Ftot.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/26/XI8sj04pTKVqoypl4NZRycjaeDrVIGUMBDsNqK5B.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/3/legacy-20-e47987e47a80051e.png: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/3/legacy-21-7be82caba0e76feb.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/3/legacy-22-15543e2c575b4911.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/3/legacy-23-6d9de490b28335ac.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/3/legacy-24-ab3afb7a8ba375be.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/3/legacy-25-40bff6f97fbb9cea.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/3/legacy-26-2d870a602c7ac5ef.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/3/legacy-27-9b9577c6ab4847e6.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/3/legacy-28-4392044366bff16d.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/3/legacy-29-024517f8400109fa.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/3/legacy-30-5308c95d4b2199e2.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/3/legacy-31-ed81e0e59df9cc64.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/3/legacy-32-981c81ae85225a24.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/3/legacy-33-bd29dbcfc3974864.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/3/legacy-34-8c4415eddde76f14.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/3/legacy-35-003d341a080bdfbc.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/30/08FrpZ5jaWPK9SndmI8LY5elXsVrgubWeoqLXrNu.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/30/0oFSzNfllwarvuuI6TF515iBP7pzfpYAA9JC2v3x.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/30/BkJfGvr8qgDzkmZvymgJYclJon9IJ2cGltyggFG5.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/30/D3iyezySOPVpZ03W7wyseTv89VyiuNUVLWtuggxA.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/30/TvGzc54kZsKHuvFJWeaI4ImX30Sl9yqZP6c7O9z3.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/30/URvIyqwFeF2e5d4RqurYzIPdBsGOKoJ6PEZWEjg5.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/30/cS1Mbk59Tv6CwLQwi3J5UzFagvzjdsWyyVEuO4pd.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/30/eeAAAEd02u1LSQxrumGxsUzpacyWCNw0S5UOJBoA.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/30/fAwuWgoMYRR56nF2iIv9chI67kKjh6gMmkNj8LJT.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/30/jKTgBK89MNdXbmod6YpqrXx4BLwURMljVflCBFUa.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/30/maVQq5zBQaRsBFesRnprWIzvkTarmGbC98p0TAd8.png: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/30/mxldz99otPkdrNskhluBzWERNaZKB0WAG9MCN6sm.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/30/tioVYAVuJlct6bMM8uDGS6DQDVmbpHb5TboabxOn.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/31/LeEGFP3Ge1Car3VVMJFo6e8pnSr9MQv311ipquFm.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/31/WMyzmoWOzFb0tPZCZ34tafkocGUzp6moZCAkDeyA.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/31/kW83jhxZYk09s0xZ3YtmGpw8mAvk1236DsxrwXEt.png: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/34/3tyHZA0TeuOViFov8i5KP05nts3girwv1rm3zr4q.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/35/K5jeWf2iD7KJqVrQNQyM0k24oBTbNNxBFCaTqNFV.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/35/LspaV2f0t1VMHNWv1qCBRSLjIk2e8B8OWKgyWdLO.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/36/1ygjXGtWlgTYIWneD8Nm9B1ZhruW6IJCA0XqgeK0.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/36/2urxlyIoJ9upYlJBbSatFvdOAMpdw7pNvTgnMSRY.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/37/6xLhueZaPIf1wwMAtdSOQIaRmR4G5VhcbsfMG5kk.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/37/SS4Ayz32ESajLaQpd7qzyFsRXN1wHge02ZNeubA8.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/38/VdvmgTK15lR1uFkNfRzRvMOw8W3KloRIWS6ur2kA.png: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/38/jy0S0gTB1aaqMDP6faXzpDEAcC0EdHw5zs8kzsPQ.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/39/8vlLdx6Y8Ra4ibyjg4NWihOYjHhULEjKPJjRTDAl.png: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/39/u2cyePdk3XhUFUrqTJXi4cu8Ndp8PBcBfMN0D0tf.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/4/legacy-36-b631f33d2376b53a.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/4/legacy-37-98d2eef5a4be3ab8.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/40/G420OSC3JOZluTE7vrVBR5SgJ9CTYT1jR9DMu9KW.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/40/IbQv66Idk05PCvW71LTCZCu5pvFZwIdupRolP3sP.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/40/NcUFSkAnkT8vTjNVeHpFPnEFUu7ydDuW7LSm23Dr.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/40/QiNjUzI6iozPVp5mbfpzAPCUTtSKCa0m6JoWpOCb.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/40/T23MI7Ma5cYQyvcp08jRJnUOH8YlcwY9mOq1LPPG.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/40/Uyz9GWlJvii3x5HIDQSH31sgKM2nFa9OMg9ys68p.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/40/jAJgGwVXU0A4DOkbKaRClXMU40loW080DCt4zCRP.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/40/l6adGxktFfc6pBAaVF72MYBmNCSNINEGDQANwkXv.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/42/Ggm8rbQHUjLrF74RrxlhZQmn3vuxu5xfUOtSydY8.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/42/HZxzmabUZCgNNwpPv8kBk4gxl09jbkh1Q6xHKAsZ.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/42/JBm3LTd5es79b3H43QOHYQjWFsZHP5ErGpxdK34H.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/42/MiqfDqZ8KviPbY9y9GdhUUe5EIbebzdF1AvRyaAy.png: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/42/Oz63HNekHkRt5qDLqklyuapIlbe6zq3etqXeRekZ.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/42/RB4zkmWDQhG180BHT99aU9f6n1z6CU2qg8nZSW1n.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/42/a6dzHW03PXjhQ8JeBI9Pzoxn7eTaTOTEmtKq9My7.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/42/jmJ2zjCOM5SH4jbl40l0WSICrppsMN52EH38tdch.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/42/opbECwplvYHGgjFNEN3ftSQCEeU5vyCo2sDuaJrF.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/42/rWxYtHjmJRZ9c1DopS6EM3IWosCtYhTT4jKjEIS4.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/42/yqjjNo14p1ibux5WrdQaB5Th9Dtw1AbcfOoehNU5.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/43/6me7UCWmti7ctDjNcfDmeNu2rYgoZhn7qvm1VdZd.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/43/6vwi96S0O1Mqs1TV31dJyTPCWuOqiGbh2JMjlHHW.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/43/759Lgx9n5fTHk2RR7tpkcD1K99vLAnfTqsPgRISU.png: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/43/7yERZkgqLWPYWpNcaF0H3XDgYETEa84GWlsNb7tS.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/43/CLuGjMhwDBd8Zsm7dlrl7nPN90EbgKSiJysn6vRy.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/43/F0FN9n7LbcQRz0JxS8YdejCrNi5zS0BoSjLox5w9.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/43/HAu6x2nPXJAbY8n36eZn1z01MHlKRwqLuVMF7Oe6.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/43/Hxn9BX8sRcSq9JW4kMEgWiApZ4D85DSeInEBBE1z.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/43/NpHgdN3HRXMG8FwUrUFRn64SjpyXdig8qytyXPnt.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/43/wFP4G43fPPEfZaFl2fSToA1AMM0DOtL0yybhx3lp.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/43/y0S5yQPMTuSkGXrQRh4SZ34cACgRzINtBd7w4aSh.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/44/2lte5SIZC1HJyWJBofsMvSnYcLerzdndmJLe8B9C.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/44/4YhZ9FqNJDtJaBpPDvDDhXWjZOKtRUiaDo1Hr1Zr.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/44/87rxlVjtBuPFeBiEYcR4DFYAtyme5ZtqUcHvcgAQ.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/44/DaWc8e6Aum7T25OnCLJQAzas1BU1JSv9qOhVUs06.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/44/GOKlj0LMMTQUvLlKQOds9zjq0IA5vP24B3fg1s5n.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/44/IEvmqoDi8ZKKVQDKmaRGYkIoAmzom30EaLBiVvfF.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/44/MbuCRYa6Mg5ycecFckeltSYlVdgtuuc0GhgSMYtp.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/44/QY6TrnN0DikK4wKy9qASrG8drtsBvw21gkXsTf8n.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/44/U73pQfQhTVqQaOzep1uXQF2urD7Cm1sHb3M9yNUo.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/44/nhJYnMXwYue69iqM69Hyrg8LfXJb2oizRn4LUxou.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/44/pCWhkjeViu3eXZZQgwQ4YWDF3Uh8gj0driFFs3au.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/44/z4mG2mw6UWWq2g6dcr46FGSwh2JsoGHscuDUELcB.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/46/6Ap3pO8J6uI70Xs7lwKPmk4QFTKHjcK9EMCHG6PA.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/46/B9FwxJHj2DYFprX9ZFmj3XfZazv0ILZAT5gV86lI.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/46/fPdctPG4ebNYGvBllJYy93cnNTTluRIfYclL8ziJ.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/46/iIvwznzk1IYPkdigDu9tOhe2Ht0sYxbhfGukCWwA.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/47/q4F85fvayO2IoqYAycv89LNPiKLPniH70U7zi4NQ.png: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/5/legacy-38-bc8872dd4b501d97.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/5/legacy-39-3d8e3c4f7e2e64c9.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/5/legacy-40-7ec27aceca4e656d.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/5/legacy-41-239eb53bc39746a7.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/5/legacy-42-331fd8e8dff6cabb.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/5/legacy-43-d7b94eb9d44b06f0.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/5/legacy-44-2573d99510456c52.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/6/legacy-45-d4a1fa1315ddd79a.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/6/legacy-46-44058fc5fa791f0c.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/6/legacy-47-bccc0737f5dd3d55.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/6/legacy-48-1d7fd2c4e53e9ce2.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/6/legacy-49-e0ff312bca477775.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/6/legacy-50-84f09b5f4570400c.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/6/legacy-51-8c71483d918a7e98.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/6/legacy-52-145bc2004940633a.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/6/legacy-53-ef179a4cec10577d.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/6/legacy-54-c87747abe7354420.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/6/legacy-55-951be2cd324fb459.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/6/legacy-56-642e9224665d5300.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/6/legacy-57-c744a0a59e477e0e.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/6/legacy-58-8ada56656165ba02.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/7/661e6197-4b34-41c4-848d-0005d946b13e.png: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/7/legacy-60-34db9253fb9ce05a.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/7/legacy-61-c7ae262648bf3a41.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/7/legacy-62-33f664edc47e5837.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/7/legacy-63-b71d0cb7928d2c84.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/7/legacy-64-ee548744ceb97e95.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/7/legacy-65-7817863ce5245c60.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/7/legacy-66-9425c2e1f68872f5.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/8/mobile-fix-67-b2e8ccc6e5708493.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/8/mobile-fix-68-4a11ff134b3fabac.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/8/mobile-fix-69-3d4bf96b04737bb3.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/8/mobile-fix-70-b53fc235cb5f3970.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/9/2T6WcqzI1coBn8cPhUFCje4cVYNaB9YrgAWxVarq.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/9/EoInbKS141JBYijIMicnqeFZcJ1RWTTJqIBmkV2i.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/9/Sou9pIVX86wcFNdG2O1y5Xk02E9B9rMtUvDIyJFe.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/9/WKNKF5LtGs2FSCvynln820rY9Jqunuplk3Xx4Oux.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/9/ZTMZGZgvDvrB9x38NRyTq1dkGSQ3BM1vvQMu5uFE.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/9/rR4kRYlegVuk9hgRkhSVkMLtNz9h03c0B88NItmT.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/files/app-public/products/9/z0PJY8LSWsGQPPRONbJDIsZOkaxH84xMIhoMv6fz.jpg: OK
./cms-runtime/20260828-112345-manual-f8aad0/manifest.json: OK
./evidence/276-final-deep-cleanup.md: OK
./evidence/277-batch61-safe-failure.md: OK
./git/ald1n-project-full.bundle: OK
./runtime/public.htaccess: OK
./runtime/public.htaccess.diff: OK
./source/ald1n-project-tracked-8d51c71b0fdc470f204d4644234b38769971ab3b.tar.gz: OK
CLEAN_STABLE_PACKAGE_HASH_VERIFY=PASS
Backup: /home/icaffeco/backups/current/ALD1N-CLEAN-STABLE-v1.0.0-cms-v2.2.0-20260828-112307/cms-runtime/20260828-112345-manual-f8aad0
PASS Backup verzija: 2.2.0.
PASS Backup je svez: 0,3 h.
PASS SQL backup SHA-256 je validan.
PASS SQL gzip je citljiv; dekompresovano 2,35 MB.
PASS SQL backup sadrzi CREATE TABLE.
PASS SQL backup sadrzi migrations tabela.
PASS Verifikovani privatni backup fajlovi: 252/252.
PASS Backup evidencija i velicina direktorijuma su uskladjene: 460,38 MB.
Backup je logicki validan i spreman za kontrolisanu restore probu na odvojenoj bazi.
CLEAN_STABLE_RUNTIME_BACKUP_VERIFY=PASS_RUN88
PASS  postoji artisan
PASS  postoji composer.json
PASS  postoji composer.lock
PASS  postoji .env.example
PASS  postoji VERSION
PASS  postoji RELEASE-TAG
PASS  postoji UPGRADE-FROM
PASS  postoji docs/UPGRADE-V2.1-BETA1.md
PASS  postoji docs/UPGRADE-V2.1-BETA1.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA1.2.md
PASS  postoji docs/UPGRADE-V2.1-BETA1.3.md
PASS  postoji docs/UPGRADE-V2.1-BETA2.md
PASS  postoji docs/UPGRADE-V2.1-BETA3.md
PASS  postoji docs/UPGRADE-V2.1-BETA3.2.md
PASS  postoji docs/UPGRADE-V2.1-BETA3.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA4.md
PASS  postoji docs/UPGRADE-V2.1-BETA5.md
PASS  postoji docs/UPGRADE-V2.1-BETA6.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.2.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.3.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.4.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.5.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.6.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.7.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.8.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.9.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.10.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.11.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.12.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.13.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.14.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.14.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.15.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.16.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.17.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.17.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.17.2.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.18.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.18.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.19.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.20.md
PASS  postoji DATABASE-MIGRATION-REQUIRED.txt
PASS  postoji app/Models/Order.php
PASS  postoji app/Models/OrderItem.php
PASS  postoji app/Models/OrderDocument.php
PASS  postoji app/Models/OrderCommission.php
PASS  postoji app/Models/CommissionStatusHistory.php
PASS  postoji app/Models/CommissionPaymentBatch.php
PASS  postoji app/Models/OrderInternalNote.php
PASS  postoji app/Models/OrderAssignment.php
PASS  postoji app/Models/OrderStatusHistory.php
PASS  postoji app/Models/StockMovement.php
PASS  postoji app/Models/IdempotencyKey.php
PASS  postoji app/Models/OrderPayment.php
PASS  postoji app/Models/OrderDelivery.php
PASS  postoji app/Models/AfterSalesCase.php
PASS  postoji app/Models/AfterSalesCaseItem.php
PASS  postoji app/Models/AfterSalesMessage.php
PASS  postoji app/Models/AfterSalesAttachment.php
PASS  postoji app/Models/AfterSalesStatusHistory.php
PASS  postoji app/Models/AfterSalesAction.php
PASS  postoji app/Models/AfterSalesActionItem.php
PASS  postoji app/Models/FieldServiceTeam.php
PASS  postoji app/Models/FieldWorkOrder.php
PASS  postoji app/Models/FieldWorkOrderAttachment.php
PASS  postoji app/Models/ServicePartSupplier.php
PASS  postoji app/Models/ServicePart.php
PASS  postoji app/Models/FieldWorkOrderPart.php
PASS  postoji app/Models/ServicePartMovement.php
PASS  postoji app/Models/ServicePartPurchaseRequest.php
PASS  postoji app/Models/ServicePartPurchaseRequestItem.php
PASS  postoji app/Models/WarrantyRule.php
PASS  postoji app/Models/ProductWarranty.php
PASS  postoji app/Models/WarrantyMaintenanceRecord.php
PASS  postoji app/Models/OrderEmailOutbox.php
PASS  postoji app/Models/StockReceipt.php
PASS  postoji app/Models/StockReceiptItem.php
PASS  postoji app/Models/InventoryCount.php
PASS  postoji app/Models/InventoryCountItem.php
PASS  postoji app/Models/AutomationRun.php
PASS  postoji app/Models/OperationalAlert.php
PASS  postoji app/Models/NotificationPreference.php
PASS  postoji app/Models/BackupRun.php
PASS  postoji app/Models/SystemHealthSnapshot.php
PASS  postoji app/Models/SystemRuntimeState.php
PASS  postoji app/Models/SecurityEvent.php
PASS  postoji app/Services/OrderService.php
PASS  postoji app/Services/OrderWorkflowService.php
PASS  postoji app/Services/InventoryService.php
PASS  postoji app/Services/IdempotencyService.php
PASS  postoji app/Services/OrderPaymentService.php
PASS  postoji app/Services/IpsPaymentPayloadService.php
PASS  postoji app/Services/AdvancedInventoryService.php
PASS  postoji app/Services/LegacyReadOnlyGuard.php
PASS  postoji app/Services/OrderAccessService.php
PASS  postoji app/Services/OrderReportService.php
PASS  postoji app/Services/CommissionReportService.php
PASS  postoji app/Services/CommissionWorkflowService.php
PASS  postoji app/Services/OrderOperationalService.php
PASS  postoji app/Services/OrderTimelineService.php
PASS  postoji app/Services/OperationalNotificationService.php
PASS  postoji app/Notifications/OperationalNotification.php
PASS  postoji app/Services/OperationalAutomationService.php
PASS  postoji app/Services/AutomationReadinessService.php
PASS  postoji app/Services/BackupService.php
PASS  postoji app/Services/SystemHealthService.php
PASS  postoji app/Services/SecurityEventLogger.php
PASS  postoji app/Services/SensitiveDataSanitizer.php
PASS  postoji app/Services/OrderIndexService.php
PASS  postoji app/Services/OrderDetailService.php
PASS  postoji app/Services/OrderDetailPresenter.php
PASS  postoji app/Support/ViewValue.php
PASS  postoji app/Services/OrderDocumentService.php
PASS  postoji app/Services/AfterSalesAccessService.php
PASS  postoji app/Services/AfterSalesCaseService.php
PASS  postoji app/Services/AfterSalesActionService.php
PASS  postoji app/Services/FieldWorkOrderPlanner.php
PASS  postoji app/Services/FieldOperationsService.php
PASS  postoji app/Services/ServicePartsInventoryService.php
PASS  postoji app/Services/WarrantyService.php
PASS  postoji app/Services/OrderEmailOutboxService.php
PASS  postoji app/Services/OrderEmailDispatcher.php
PASS  postoji app/Services/NbsIpsQrService.php
PASS  postoji app/Services/DocumentNumberService.php
PASS  postoji app/Services/Pdf/SimplePdfWriter.php
PASS  postoji app/Services/Pdf/BusinessDocumentPdfService.php
PASS  postoji app/Services/Pdf/WarrantyCertificatePdfService.php
PASS  postoji app/Http/Requests/StoreOrderRequest.php
PASS  postoji app/Http/Requests/AdjustStockRequest.php
PASS  postoji app/Http/Requests/StoreAfterSalesCaseRequest.php
PASS  postoji app/Http/Requests/StoreAfterSalesMessageRequest.php
PASS  postoji app/Http/Requests/UpdateAfterSalesCaseRequest.php
PASS  postoji app/Http/Requests/StoreAfterSalesActionRequest.php
PASS  postoji app/Http/Requests/CompleteAfterSalesActionRequest.php
PASS  postoji app/Http/Requests/CancelAfterSalesActionRequest.php
PASS  postoji app/Http/Requests/StoreFieldServiceTeamRequest.php
PASS  postoji app/Http/Requests/UpdateFieldServiceTeamRequest.php
PASS  postoji app/Http/Requests/ScheduleFieldWorkOrderRequest.php
PASS  postoji app/Http/Requests/CompleteFieldWorkOrderRequest.php
PASS  postoji app/Http/Requests/CancelFieldWorkOrderRequest.php
PASS  postoji app/Http/Requests/StoreServicePartRequest.php
PASS  postoji app/Http/Requests/UpdateServicePartRequest.php
PASS  postoji app/Http/Requests/AdjustServicePartStockRequest.php
PASS  postoji app/Http/Requests/StoreServicePartSupplierRequest.php
PASS  postoji app/Http/Requests/UpdateServicePartSupplierRequest.php
PASS  postoji app/Http/Requests/StoreFieldWorkOrderPartRequest.php
PASS  postoji app/Http/Requests/StoreServicePartPurchaseRequest.php
PASS  postoji app/Http/Requests/CancelServicePartPurchaseRequest.php
PASS  postoji app/Http/Requests/StoreWarrantyRuleRequest.php
PASS  postoji app/Http/Requests/UpdateProductWarrantyRequest.php
PASS  postoji app/Http/Requests/ScheduleWarrantyMaintenanceRequest.php
PASS  postoji app/Http/Requests/CompleteWarrantyMaintenanceRequest.php
PASS  postoji app/Http/Controllers/OrderController.php
PASS  postoji app/Http/Controllers/WarrantyController.php
PASS  postoji app/Http/Controllers/AfterSalesController.php
PASS  postoji app/Http/Controllers/AfterSalesAttachmentController.php
PASS  postoji app/Http/Controllers/FieldWorkOrderAttachmentController.php
PASS  postoji app/Http/Controllers/CommissionController.php
PASS  postoji app/Http/Controllers/NotificationController.php
PASS  postoji app/Http/Controllers/Api/V1/OrderController.php
PASS  postoji app/Http/Controllers/Admin/OrderController.php
PASS  postoji app/Http/Controllers/Admin/AfterSalesController.php
PASS  postoji app/Http/Controllers/Admin/AfterSalesActionController.php
PASS  postoji app/Http/Controllers/Admin/FieldOperationsController.php
PASS  postoji app/Http/Controllers/Admin/FieldServiceTeamController.php
PASS  postoji app/Http/Controllers/Admin/ServicePartController.php
PASS  postoji app/Http/Controllers/Admin/ServicePartSupplierController.php
PASS  postoji app/Http/Controllers/Admin/FieldWorkOrderPartController.php
PASS  postoji app/Http/Controllers/Admin/ServicePartPurchaseRequestController.php
PASS  postoji app/Http/Controllers/Admin/WarrantyController.php
PASS  postoji app/Http/Controllers/Admin/CommissionController.php
PASS  postoji app/Http/Controllers/Admin/StockAdjustmentController.php
PASS  postoji app/Http/Controllers/OrderDocumentController.php
PASS  postoji app/Http/Controllers/Admin/OrderDocumentController.php
PASS  postoji app/Http/Controllers/Admin/ReportController.php
PASS  postoji app/Http/Controllers/Admin/DocumentSettingsController.php
PASS  postoji app/Http/Controllers/OrderPaymentController.php
PASS  postoji app/Http/Controllers/OrderDeliveryController.php
PASS  postoji app/Http/Controllers/Admin/PaymentController.php
PASS  postoji app/Http/Controllers/Admin/InventoryController.php
PASS  postoji app/Http/Controllers/Admin/AutomationController.php
PASS  postoji app/Http/Controllers/Admin/SystemHealthController.php
PASS  postoji app/Http/Controllers/Admin/TurnstileSettingsController.php
PASS  postoji app/Http/Controllers/Admin/OrderEmailSettingsController.php
PASS  postoji app/Http/Resources/OrderResource.php
PASS  postoji app/Console/Commands/OrdersDoctorCommand.php
PASS  postoji app/Console/Commands/OrderCreateDoctorCommand.php
PASS  postoji app/Console/Commands/CatalogOwnershipDoctorCommand.php
PASS  postoji app/Console/Commands/DetailPagesDoctorCommand.php
PASS  postoji app/Console/Commands/ReportsDoctorCommand.php
PASS  postoji app/Console/Commands/OperationsDoctorCommand.php
PASS  postoji app/Console/Commands/PaymentsInventoryDoctorCommand.php
PASS  postoji app/Console/Commands/RunOperationalAutomationCommand.php
PASS  postoji app/Console/Commands/AutomationDoctorCommand.php
PASS  postoji app/Console/Commands/CreateBackupCommand.php
PASS  postoji app/Console/Commands/BackupDoctorCommand.php
PASS  postoji app/Console/Commands/SystemHealthCommand.php
PASS  postoji app/Console/Commands/SchedulerHeartbeatCommand.php
PASS  postoji app/Console/Commands/TestDatabaseDoctorCommand.php
PASS  postoji app/Console/Commands/AfterSalesDoctorCommand.php
PASS  postoji app/Console/Commands/FieldOperationsDoctorCommand.php
PASS  postoji app/Console/Commands/ServicePartsDoctorCommand.php
PASS  postoji app/Console/Commands/WarrantiesDoctorCommand.php
PASS  postoji app/Console/Commands/WarrantiesBackfillCommand.php
PASS  postoji app/Console/Commands/OrderEmailDispatchCommand.php
PASS  postoji app/Console/Commands/OrderEmailsDoctorCommand.php
PASS  postoji database/migrations/2026_07_22_000006_enable_production_orders_inventory.php
PASS  postoji database/migrations/2026_07_23_000007_repair_production_schema_beta5.php
PASS  postoji database/migrations/2026_07_23_000008_repair_authenticated_runtime_beta6.php
PASS  postoji database/migrations/2026_07_23_000009_create_reports_documents_and_supplier_assignment.php
PASS  postoji database/migrations/2026_07_23_000010_repair_reports_schema_beta1_2.php
PASS  postoji database/migrations/2026_07_23_000011_create_operational_orders_commissions_beta2.php
PASS  postoji database/migrations/2026_07_23_000012_create_payments_advanced_inventory_beta3.php
PASS  postoji database/migrations/2026_07_23_000013_create_automation_alerts_beta4.php
PASS  postoji database/migrations/2026_07_23_000014_create_security_backup_health_beta6.php
PASS  postoji database/migrations/2026_07_29_000015_repair_order_documents_and_payments_beta7_5.php
PASS  postoji database/migrations/2026_07_30_000016_add_order_completion_beta7_7.php
PASS  postoji database/migrations/2026_07_30_000017_add_delivery_workflow_beta7_8.php
PASS  postoji database/migrations/2026_07_30_000018_fix_delivery_note_document_type_beta7_9.php
PASS  postoji database/migrations/2026_07_30_000019_create_after_sales_cases_beta7_10.php
PASS  postoji database/migrations/2026_07_30_000020_create_after_sales_actions_beta7_11.php
PASS  postoji database/migrations/2026_07_30_000021_create_field_operations_beta7_12.php
PASS  postoji database/migrations/2026_07_30_000022_create_service_parts_procurement_beta7_13.php
PASS  postoji database/migrations/2026_07_30_000023_enable_document_revisions_beta7_14.php
PASS  postoji database/migrations/2026_07_30_000024_create_warranties_preventive_maintenance_beta7_15.php
PASS  postoji database/migrations/2026_07_30_000025_create_order_email_outbox_beta7_16.php
PASS  postoji resources/views/orders/index.blade.php
PASS  postoji resources/views/admin/orders/show.blade.php
PASS  postoji resources/views/commissions/index.blade.php
PASS  postoji resources/views/notifications/index.blade.php
PASS  postoji resources/views/admin/commissions/index.blade.php
PASS  postoji resources/views/orders/create.blade.php
PASS  postoji resources/views/orders/show.blade.php
PASS  postoji resources/views/admin/reports/index.blade.php
PASS  postoji resources/views/admin/settings/documents.blade.php
PASS  postoji resources/views/admin/inventory/index.blade.php
PASS  postoji resources/views/admin/orders/partials/payments.blade.php
PASS  postoji resources/views/orders/partials/payments.blade.php
PASS  postoji resources/views/after-sales/index.blade.php
PASS  postoji resources/views/after-sales/create.blade.php
PASS  postoji resources/views/after-sales/show.blade.php
PASS  postoji resources/views/admin/after-sales/index.blade.php
PASS  postoji resources/views/admin/after-sales/show.blade.php
PASS  postoji resources/views/admin/field-operations/index.blade.php
PASS  postoji resources/views/admin/field-operations/show.blade.php
PASS  postoji resources/views/admin/field-operations/teams.blade.php
PASS  postoji resources/views/admin/service-parts/index.blade.php
PASS  postoji resources/views/admin/service-parts/suppliers.blade.php
PASS  postoji resources/views/admin/service-parts/purchase-requests.blade.php
PASS  postoji resources/views/admin/service-parts/purchase-show.blade.php
PASS  postoji resources/views/admin/settings/automation.blade.php
PASS  postoji resources/views/admin/settings/system-health.blade.php
PASS  postoji resources/views/admin/settings/turnstile.blade.php
PASS  postoji resources/views/admin/settings/order-emails.blade.php
PASS  postoji resources/views/emails/order-events.blade.php
PASS  postoji tests/Feature/AdminOrdersImageRotationTest.php
PASS  postoji tests/Feature/OperationalOrdersCommissionsTest.php
PASS  postoji tests/Feature/OperationalAutomationTest.php
PASS  postoji tests/Feature/PaymentsAdvancedInventoryTest.php
PASS  postoji tests/Feature/OrderDeliveryWorkflowTest.php
PASS  postoji tests/Feature/AfterSalesWorkflowTest.php
PASS  postoji tests/Feature/AfterSalesActionExecutionTest.php
PASS  postoji tests/Feature/FieldOperationsWorkflowTest.php
PASS  postoji tests/Feature/ServicePartsWorkflowTest.php
PASS  postoji tests/Feature/InventoryWorkspaceUiTest.php
PASS  postoji tests/Feature/SecurityHealthBackupTest.php
PASS  postoji tests/Feature/MySqlTestDatabaseSafetyTest.php
PASS  postoji tests/Unit/SensitiveDataSanitizerTest.php
PASS  postoji tests/Feature/ProductionOrderInventoryTest.php
PASS  postoji tests/Feature/InventoryAdjustmentTest.php
PASS  postoji tests/Feature/ProductionPermissionsTest.php
PASS  postoji tests/Feature/DashboardLegacyDesignTest.php
PASS  postoji tests/Feature/ReportsDocumentsSupplierTest.php
PASS  postoji tests/Fixtures/pdf-logo.jpg
PASS  postoji tests/Unit/BusinessDocumentPdfServiceTest.php
PASS  postoji tests/Unit/DeliveryNoteMigrationContractTest.php
PASS  postoji tests/Unit/DocumentRevisionMigrationContractTest.php
PASS  postoji tests/Unit/CommissionReportPdfServiceTest.php
PASS  postoji tests/Feature/OrderEmailsIpsWarrantyTest.php
PASS  postoji tests/Unit/OrderEmailIpsMigrationContractTest.php
PASS  postoji tests/Unit/ReceivablesPermissionMigrationContractTest.php
PASS  postoji tests/Unit/LegacyReadOnlyGuardTest.php
PASS  postoji tests/Unit/OrderDetailPresenterTest.php
PASS  postoji tests/Unit/ViewValueTest.php
PASS  postoji tests/Feature/CatalogDetailPageTest.php
PASS  postoji tests/Feature/LoginDashboardFallbackTest.php
PASS  postoji resources/views/components/icon.blade.php
PASS  postoji app/Http/Middleware/EnsureRuntimeDirectories.php
PASS  postoji app/Http/Middleware/AttachRequestId.php
PASS  postoji app/Http/Middleware/SecurityHeaders.php
PASS  postoji app/Console/Commands/AuthDoctorCommand.php
PASS  postoji .env.testing.mysql.example
PASS  postoji phpunit.mysql.xml
PASS  postoji bin/php-lint.php
PASS  postoji bin/autoload-check.php
PASS  postoji bin/pdf-smoke.php
PASS  postoji bin/delivery-note-smoke.php
PASS  postoji bin/warranty-pdf-smoke.php
PASS  postoji bin/ips-qr-pdf-smoke.php
PASS  postoji storage/framework/cache/data/.gitignore
PASS  postoji storage/framework/sessions/.gitignore
PASS  postoji storage/framework/views/.gitignore
PASS  postoji storage/logs/.gitignore
PASS  postoji storage/app/backups/.gitignore
PASS  postoji config/backup.php
PASS  postoji docs/UPGRADE-V2.1-BETA7.23.md
PASS  postoji docs/RELEASE-CHECK.md
PASS  postoji app/Console/Commands/ReleaseCheckCommand.php
PASS  postoji config/release.php
PASS  postoji bin/release-check-smoke.php
PASS  postoji tests/Unit/ReleaseCheckContractTest.php
PASS  postoji tests/Feature/ReleaseCheckCommandTest.php
PASS  postoji storage/app/release-check/.gitignore
PASS  postoji docs/UPGRADE-V2.1-BETA7.22.1.md
PASS  postoji bin/theme-css-smoke.php
PASS  postoji docs/UPGRADE-V2.1-BETA7.21.md
PASS  postoji database/migrations/2026_07_31_000030_create_management_reports_beta7_21.php
PASS  postoji app/Models/ReportSchedule.php
PASS  postoji app/Models/ReportDelivery.php
PASS  postoji app/Services/ManagementReportService.php
PASS  postoji app/Services/ReportScheduleService.php
PASS  postoji app/Services/Pdf/ManagementReportPdfService.php
PASS  postoji app/Http/Controllers/Admin/ManagementReportController.php
PASS  postoji app/Http/Controllers/Admin/ReportScheduleController.php
PASS  postoji app/Console/Commands/ManagementReportsDoctorCommand.php
PASS  postoji app/Console/Commands/OrderCostSnapshotsCommand.php
PASS  postoji app/Services/OrderItemCostSnapshotService.php
PASS  postoji app/Console/Commands/ManagementReportsDispatchCommand.php
PASS  postoji resources/views/admin/reports/management.blade.php
PASS  postoji resources/views/emails/management-report.blade.php
PASS  postoji tests/Feature/ManagementReportsProfitabilityTest.php
PASS  postoji tests/Unit/OrderCostSnapshotRepairContractTest.php
PASS  postoji bin/management-report-smoke.php
PASS  postoji bin/order-cost-snapshot-smoke.php
PASS  postoji docs/UPGRADE-V2.1-BETA7.19.md
PASS  postoji database/migrations/2026_07_31_000028_create_smart_product_management_beta7_19.php
PASS  postoji app/Services/ProductTemplateService.php
PASS  postoji app/Services/ProductCompletenessService.php
PASS  postoji app/Services/ProductBulkService.php
PASS  postoji app/Http/Controllers/Admin/ProductBulkController.php
PASS  postoji app/Console/Commands/SmartProductsDoctorCommand.php
PASS  postoji resources/views/admin/products/clone.blade.php
PASS  postoji resources/views/admin/products/bulk.blade.php
PASS  postoji tests/Unit/SmartProductManagementMigrationContractTest.php
PASS  postoji tests/Unit/SmartProductManagementUiContractTest.php
PASS  postoji tests/Feature/SmartProductManagementTest.php
PASS  postoji bin/smart-product-smoke.php
PASS  postoji docs/UPGRADE-V2.1-BETA7.18.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.19.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.23.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.23.2.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.24.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.24.1.md
PASS  postoji docs/UPGRADE-V2.1-RC1.md
PASS  postoji docs/RC-OPERATIONS.md
PASS  postoji docs/UPGRADE-V2.1-STABLE.md
PASS  postoji docs/UPGRADE-V2.1.1.md
PASS  postoji docs/UPGRADE-V2.1.2.md
PASS  postoji docs/UPGRADE-V2.1.3.md
PASS  postoji docs/UPGRADE-V2.1.3.1.md
PASS  postoji docs/UPGRADE-V2.1.3.2.md
PASS  postoji docs/UPGRADE-V2.1.3.3.md
PASS  postoji docs/STABLE-OPERATIONS.md
PASS  postoji docs/BACKUP-RESTORE-DRILL.md
PASS  postoji bin/rc-hardening-smoke.php
PASS  postoji bin/stable-hardening-smoke.php
PASS  postoji bin/stable-maintenance-smoke.php
PASS  postoji bin/product-media-ux-smoke.php
PASS  postoji bin/product-announcement-smoke.php
PASS  postoji bin/catalog-settings-product-data-smoke.php
PASS  postoji bin/catalog-settings-integrity-hotfix-smoke.php
PASS  postoji bin/product-save-regex-hotfix-smoke.php
PASS  postoji bin/storage-capacity-total-smoke.php
PASS  postoji tests/Unit/ProductSaveRegexHotfixContractTest.php
PASS  postoji tests/Unit/StorageCapacityTotalContractTest.php
PASS  postoji tests/Unit/CatalogSettingsProductDataContractTest.php
PASS  postoji tests/Unit/CatalogSettingsIntegrityHotfixContractTest.php
PASS  postoji app/Console/Commands/CatalogSettingsDoctorCommand.php
PASS  postoji app/Services/ProductTypeCategoryService.php
PASS  postoji app/Services/SpecificationFieldLifecycleService.php
PASS  postoji app/Services/StorageSpecificationService.php
PASS  postoji database/migrations/2026_08_04_000033_create_catalog_type_layout_v2_1_3.php
PASS  postoji database/migrations/2026_08_04_000034_repair_catalog_category_and_spec_integrity_v2_1_3_1.php
PASS  postoji database/migrations/2026_08_04_000035_link_storage_components_and_total_capacity_v2_1_3_3.php
PASS  postoji public/assets/js/dictionary-sort-manager.js
PASS  postoji resources/views/admin/dictionary/product-type.blade.php
PASS  postoji tests/Unit/ProductMediaUxContractTest.php
PASS  postoji tests/Unit/ProductAnnouncementContractTest.php
PASS  postoji app/Services/ProductAnnouncementService.php
PASS  postoji tests/Unit/ReleaseCandidateHardeningContractTest.php
PASS  postoji tests/Unit/StableReleaseContractTest.php
PASS  postoji tests/Unit/StableMaintenanceContractTest.php
PASS  postoji app/Console/Commands/SecurityHardeningDoctorCommand.php
PASS  postoji app/Console/Commands/MigrationsDoctorCommand.php
PASS  postoji app/Console/Commands/AccessControlDoctorCommand.php
PASS  postoji app/Console/Commands/ReleaseIntegrityCommand.php
PASS  postoji app/Console/Commands/BackupVerifyCommand.php
PASS  postoji app/Console/Commands/ProductMediaDoctorCommand.php
PASS  postoji app/Http/Controllers/ProductMediaDownloadController.php
PASS  postoji public/assets/js/product-media-manager.js
PASS  postoji resources/views/admin/products/partials/image-card.blade.php
PASS  postoji resources/views/admin/products/partials/image-upload.blade.php
PASS  postoji bin/catalog-detail-smoke.php
PASS  postoji bin/detail-pages-doctor-smoke.php
PASS  postoji tests/Unit/CatalogDetailBladeContractTest.php
PASS  postoji tests/Unit/SystemHealthRemediationContractTest.php
PASS  postoji tests/Unit/DetailPagesDoctorContractTest.php
PASS  postoji database/migrations/2026_07_31_000027_create_correlated_specifications_beta7_18.php
PASS  postoji app/Models/SpecificationOption.php
PASS  postoji app/Services/SpecificationDependencyService.php
PASS  postoji app/Services/CatalogSpecificationFilterService.php
PASS  postoji app/Console/Commands/CatalogCorrelationsDoctorCommand.php
PASS  postoji resources/views/partials/correlated-specification-filters.blade.php
PASS  postoji resources/views/partials/correlated-specification-filter-script.blade.php
PASS  postoji tests/Unit/CorrelatedSpecificationsMigrationContractTest.php
PASS  postoji tests/Unit/CorrelatedSpecificationUiContractTest.php
PASS  postoji docs/UPGRADE-V2.1.4.md
PASS  postoji bin/cms-v2.1.4-smoke.php
PASS  postoji tests/Unit/CmsV214ContractTest.php
PASS  postoji app/Console/Commands/CmsV214DoctorCommand.php
PASS  postoji app/Services/ProductDeletionService.php
PASS  postoji database/migrations/2026_08_04_000036_add_product_model_and_name_templates_v2_1_4.php
PASS  postoji docs/UPGRADE-V2.1.4.1.md
PASS  postoji bin/product-type-page-render-hotfix-smoke.php
PASS  postoji tests/Unit/ProductTypePageRenderHotfixContractTest.php
PASS  postoji docs/UPGRADE-V2.1.5.md
PASS  postoji bin/cms-v2.1.5-smoke.php
PASS  postoji tests/Unit/CmsV215ContractTest.php
PASS  postoji app/Console/Commands/CmsV215DoctorCommand.php
PASS  postoji public/assets/js/ux-runtime.js
PASS  postoji resources/views/errors/minimal.blade.php
PASS  postoji resources/views/errors/403.blade.php
PASS  postoji resources/views/errors/404.blade.php
PASS  postoji resources/views/errors/419.blade.php
PASS  postoji resources/views/errors/429.blade.php
PASS  postoji resources/views/errors/500.blade.php
PASS  postoji resources/views/errors/503.blade.php
PASS  postoji database/migrations/2026_08_05_000037_place_desktop_power_supply_field_v2_1_5.php
PASS  postoji docs/UPGRADE-V2.2.0.md
PASS  postoji docs/openapi.yaml
PASS  postoji bin/cms-v2.2.0-smoke.php
PASS  postoji tests/Feature/MobileApiFoundationTest.php
PASS  postoji tests/Unit/MobileApiFoundationContractTest.php
PASS  postoji app/Console/Commands/CmsV220DoctorCommand.php
PASS  postoji app/Http/Controllers/Api/V1/BootstrapController.php
PASS  postoji app/Http/Controllers/Api/V1/MobileDeviceController.php
PASS  postoji app/Models/MobileDevice.php
PASS  postoji database/migrations/2026_08_06_000039_create_mobile_devices_v2_2_0.php
PASS  postoji database/migrations/2026_08_06_000040_add_push_notification_preference_v2_2_0.php
PASS  postoji database/migrations/2026_08_06_000041_create_database_queue_tables_v2_2_0.php
PASS  verzija je 2.2.0 Mobile API Foundation
PASS  release tag je v2.2.0
PASS  upgrade osnova je v2.1.6
PASS  composer.json validan
PASS  PHP minimum 8.4
PASS  Laravel 13
PASS  Composer lint/autoload/test/release skripte postoje
PASS  runtime verzija je 2.2.0
PASS  migracija sadrži idempotency_keys
PASS  migracija sadrži source_system
PASS  migracija sadrži inventory_state
PASS  migracija sadrži inventory_returned_at
PASS  migracija sadrži event_key
PASS  migracija sadrži orders_user_idempotency_unique
PASS  porudžbina zaključava proizvode
PASS  porudžbina umanjuje lager u transakciji
PASS  povrat lagera ima jedinstveni event key
PASS  idempotency koristi unique zapis i row lock
PASS  legacy porudžbine su blokirane
PASS  legacy SQL guard je registrovan pre izvršavanja
PASS  legacy MySQL sesija je READ ONLY
PASS  Redis je uklonjen iz database konfiguracije
PASS  Redis je uklonjen iz cache konfiguracije
PASS  Redis je uklonjen iz queue konfiguracije
PASS  login rate limiter koristi file store
PASS  dozvola orders.create
PASS  dozvola orders.view_own
PASS  dozvola orders.cancel_own
PASS  dozvola orders.manage
PASS  dozvola stock.view
PASS  dozvola stock.adjust
PASS  dozvola reports.view
PASS  dozvola reports.export
PASS  dozvola invoices.manage
PASS  dozvola invoices.view_own
PASS  web ruta orders.store
PASS  web ruta orders.cancel
PASS  web ruta admin.orders.status
PASS  web ruta admin.orders.payment
PASS  web ruta admin.orders.tracking
PASS  web ruta admin.stock.adjust
PASS  API porudžbine postoje
PASS  porudžbina ima dodeljenog SuperAdmin/Admin dobavljača
PASS  admin scope vidi samo njemu dodeljene porudžbine
PASS  izveštaji podržavaju filtere i CSV/PDF
PASS  poslovni dokumenti koriste nepromenljivi snapshot
PASS  PDF renderer je lokalni, embedded TrueType i ToUnicode
PASS  brojevi dokumenata su transakcioni i jedinstveni
PASS  web rute imaju reports CSV/PDF i dokumente
PASS  reports stranica ima schema fallback umesto 500
PASS  reports render je unutar zaštićenog controller toka
PASS  reports view ima render marker i bezbedne URL-ove
PASS  reports export vraća kontrolisani 503
PASS  reports doctor izvršava repair i stvarne SQL upite
PASS  reports doctor renderuje controller Blade i layout
PASS  reports logging je best-effort
PASS  beta1.2 repair migracija je nedestruktivna
PASS  hamburger dugme postoji
PASS  mobilni meni ima kontrolni JavaScript
PASS  mobilni meni nema horizontalni scroll
PASS  direktne mobilne stavke koriste zajednički levi wrapper
PASS  Početna Provizije i Izveštaji su poravnati ulevo
PASS  CSS ima pouzdan cache busting
PASS  legacy desktop header ima dva reda
PASS  legacy mobilni header zadržava kurs temu nalog i hamburger
PASS  dashboard ima moderni hero KPI prioritete i module
PASS  dashboard CSS ima 4 desktop i 2 mobilne kolone
PASS  admin gridovi su poravnati na vrh
PASS  forme koriste sadržajnu visinu
PASS  deployment check ima bezbedan repair režim
PASS  deployment check razlikuje runtime zaštitu i grant warning
PASS  deployment check proverava i operativne kolone
PASS  dashboard koristi DB fallback umesto 500
PASS  login telemetry je best-effort
PASS  login hvata session i remember-token probleme
PASS  authenticated layout nema direktan SettingsService upit
PASS  authenticated layout koristi bezbedne user helper metode
PASS  dashboard logging ne može da obori fallback
PASS  runtime middleware prethodi session/cache middleware-u
PASS  deployment repair kreira runtime direktorijume i kompajlira Blade
PASS  auth doctor može da renderuje kompletan dashboard
PASS  Turnstile hvata sve transportne/JSON greške
PASS  Turnstile podešavanja imaju DB prioritet i env fallback
PASS  Turnstile secret se čuva šifrovano i ne izlaže kroz all
PASS  Turnstile admin ekran i ruta postoje
PASS  beta6 repair migracija popravlja core login šemu
PASS  static check razdvaja runtime i ZIP režim
PASS  operativna migracija sadrži order_internal_notes
PASS  operativna migracija sadrži order_assignments
PASS  operativna migracija sadrži commission_payment_batches
PASS  operativna migracija sadrži notifications
PASS  operativna migracija sadrži payment_batch_id
PASS  operativna migracija sadrži status_updated_at
PASS  operativna migracija sadrži last_internal_note_at
PASS  beta2 dozvola commissions.view_own
PASS  beta2 dozvola orders.reassign
PASS  beta2 dozvola orders.internal_notes
PASS  beta2 dozvola notifications.view
PASS  provizije imaju odobravanje isplatu storniranje i istoriju
PASS  masovna isplata koristi transakciju row lock i batch
PASS  korisnik vidi samo svoje provizije i podrazumevanih 10 procenata
PASS  interne napomene nisu u javnom timeline-u
PASS  ponovna dodela je ograničena na SuperAdministratora
PASS  preuzimanje i rokovi porudžbine imaju audit i obaveštenja
PASS  database notifikacije su neblokirajuće i mail je opcioni
PASS  operativni doctor proverava šemu SQL i render
PASS  admin provizije imaju filtere CSV PDF i masovnu isplatu
PASS  commission tabela nema unutrašnji vertikalni scroll pri obradi
PASS  obrada provizije koristi veliki viewport modal
PASS  commission modal ima naslov i eksplicitno zatvaranje
PASS  otvaranje commission modala zatvara prethodni
PASS  porudžbina ima timeline interne napomene preuzimanje rokove i reassignment UI
PASS  inbox obaveštenja podržava read i read-all
PASS  operativni feature testovi postoje
PASS  commission modal regresioni feature test postoji
PASS  beta3.1 migracija nema globalni use Throwable
PASS  beta3.1 migracija koristi potpuno kvalifikovani Throwable
PASS  PHP lint odbija warning deprecated i notice izlaz
PASS  beta3 migracija sadrži order_payments
PASS  beta3 migracija sadrži stock_receipts
PASS  beta3 migracija sadrži stock_receipt_items
PASS  beta3 migracija sadrži inventory_counts
PASS  beta3 migracija sadrži inventory_count_items
PASS  beta3 migracija sadrži payment_state
PASS  beta3 migracija sadrži paid_total_rsd
PASS  beta3 migracija sadrži payment_due_at
PASS  beta3 dozvola payments.manage
PASS  beta3 dozvola payments.upload_proof
PASS  beta3 dozvola payments.view_own
PASS  beta3 dozvola inventory.receive
PASS  beta3 dozvola inventory.count
PASS  beta3 dozvola inventory.export
PASS  uplate koriste transakciju row lock audit i saldo
PASS  potvrde uplate su privatne i autorizovane
PASS  IPS podaci koriste snapshot porudžbine
PASS  predračun i račun postavljaju dospeće porudžbine
PASS  ulaz robe i popis koriste idempotency transakciju i row lock
PASS  napredni lager ima readiness fallback umesto 500
PASS  reports beta3 sažeci i izvozi su zaštićeni
PASS  beta3 doctor proverava repair SQL i render
PASS  beta3 feature testovi pokrivaju uplate ulaz i popis
PASS  beta7.5 repair migracija obnavlja PDF i payment šemu
PASS  beta7.5 repair migracija je nedestruktivna
PASS  beta7.5 potvrda koristi site name fallback
PASS  beta7.5 ručno evidentiranje uplate ima regresioni test
PASS  beta7.5 doctor proverava dokument i payment tabele
PASS  beta7.6 PDF dozvoljava lokalno uvezene porudžbine
PASS  beta7.6 uplate dozvoljavaju lokalno uvezene porudžbine
PASS  beta7.6 legacy lager zaštita ostaje aktivna
PASS  beta7.7 migracija dodaje terminalno stanje porudžbine
PASS  beta7.7 PDF podešavanja imaju upload pregled i uklanjanje logotipa
PASS  beta7.7 PDF logo se ugrađuje kao lokalni JPEG
PASS  beta7.7 PDF ne prikazuje subagent email kupca
PASS  beta7.7 kompletiranje COD porudžbine evidentira preostali saldo
PASS  beta7.7 kompletirana porudžbina zaključava dalje izmene
PASS  beta7.7 kompletiranje je jasno dostupno u detalju i listi
PASS  beta7.8 migracija dodaje evidenciju isporuke i reopening stanje
PASS  beta7.8 kompletiranje čuva dokaz isporuke privatno
PASS  beta7.8 otpremnica koristi OTP broj i delivery snapshot
PASS  beta7.8 ponovno otvaranje je superadmin-only i auditovano
PASS  beta7.8 detalj prikazuje strukturiranu evidenciju isporuke
PASS  beta7.8 doctor proverava novu šemu i dozvole
PASS  beta7.8 feature testovi pokrivaju dokaz otpremnicu i reopening
PASS  beta7.8 UI ima delivery workflow responsive stilove
PASS  beta7.9 migracija uklanja legacy ENUM blokadu za delivery_note
PASS  beta7.9 servis radi schema preflight pre izdavanja otpremnice
PASS  beta7.9 pomoćni notification kvar ne obara izdat dokument, a IPS važi samo za finansijske dokumente
PASS  beta7.9 kontroleri vraćaju incident poruku umesto Error 500
PASS  beta7.9 doctor proverava stvarni MySQL tip dokumenta
PASS  beta7.9 ima migration contract i delivery note PDF smoke test
PASS  detail koristi eksplicitan slug upit
PASS  slug upit primenjuje objedinjeni visibility scope
PASS  API detail koristi isti slug upit
PASS  neispravna slika ne obara detail
PASS  detail filtrira slike bez validnog URL-a
PASS  katalog generiše eksplicitan slug link
PASS  detail ima interaktivnu thumbnail galeriju
PASS  detail ima fullscreen lightbox i zoom kontrole
PASS  gallery podržava tastaturu swipe i preload
PASS  gallery radi i sa jednom slikom
PASS  gallery CSS ima fullscreen viewport i responsive mobile
PASS  gallery feature testovi postoje
PASS  beta4 migracija sadrži automation_runs
PASS  beta4 migracija sadrži operational_alerts
PASS  beta4 migracija sadrži notification_preferences
PASS  beta4 nema Redis i koristi scheduler/file lock
PASS  beta4 detektuje nepreuzete porudžbine dospele obaveze i nizak lager
PASS  beta4 upozorenja su deduplikovana i razrešavaju se
PASS  notification preferences upravljaju kanalima i kategorijama
PASS  automation settings UI i ručno pokretanje postoje
PASS  automation doctor proverava repair scheduler i run
PASS  beta4 dozvola automation.manage postoji
PASS  beta4 feature testovi pokrivaju deduplikaciju i preference
PASS  beta5 inventory koristi jednu aktivnu operaciju
PASS  beta5 inventory čuva filter i limit nakon knjiženja
PASS  beta5 inventory nema unutrašnji vertikalni scrollbar
PASS  beta5 inventory responsive tabela koristi data-label kartice
PASS  beta5 feature test pokriva inventory workspace
PASS  beta6 migracija sadrži backup_runs
PASS  beta6 migracija sadrži system_health_snapshots
PASS  beta6 migracija sadrži system_runtime_states
PASS  beta6 migracija sadrži security_events
PASS  beta6 migracija sadrži system.health
PASS  beta6 migracija sadrži backups.manage
PASS  beta6 migracija sadrži audit.export
PASS  beta6 migracija sadrži security.view
PASS  beta6 backup koristi mysqldump bez lozinke u argumentima
PASS  beta6 backup odbija public putanju i pravi SHA-256 manifest
PASS  beta6 system health proverava scheduler backup migracije i legacy
PASS  beta6 security header-i i request ID postoje
PASS  beta6 audit koristi rekurzivnu sanitizaciju i request ID
PASS  beta6 rate limiter-i pokrivaju upload export admin i backup
PASS  beta6 test DB doctor ima višestruku zaštitu
PASS  beta6 system health UI i backup akcije postoje
PASS  beta6 scheduler ima heartbeat backup i health snapshot
PASS  beta6 feature i unit testovi postoje
PASS  beta7.1 orders ima readiness SQL i render zaštitu
PASS  beta7.1 orders doctor proverava isti browser render
PASS  beta7.1 orders recovery ne završava generičkim 500
PASS  beta7.1 edit artikla ima rotaciju ulevo i udesno
PASS  beta7.1 legacy rotacija koristi copy-on-write
PASS  beta7.1 rotacija koristi privremeni fajl i kontrolisani Imagick/GD fallback
PASS  beta7.1 feature testovi postoje
PASS  beta7.2 order detail koristi opcioni schema-aware loader
PASS  beta7.2 admin i user detail imaju protected render
PASS  beta7.2 admin i user detail imaju readiness markere
PASS  beta7.2 timeline i IPS ne mogu oboriti detalj
PASS  beta7.2 orders doctor renderuje oba detalja
PASS  beta7.2 detail-pages doctor proverava ključne detail stranice
PASS  beta7.2 feature testovi pokrivaju detail i opcione tabele
PASS  beta7.3 detail koristi scalar presenter umesto Eloquent objekata u Blade-u
PASS  beta7.3 presenter bezbedno obrađuje raw i zero datume
PASS  beta7.3 presenter bezbedno generiše named rute
PASS  beta7.3 detail view nema direktne auth, relation ili datetime pozive
PASS  beta7.3 admin i user detail imaju ne-503 read-only fallback
PASS  beta7.3 orders doctor prikazuje tačan exception uzrok za oba detaila
PASS  beta7.3 orders doctor nastavlja admin i user audit
PASS  beta7.3 payment i inventory Gates su definisani
PASS  beta7.3 presenter i ViewValue regresioni testovi postoje
PASS  beta7.10 migracija sadrži after_sales_cases
PASS  beta7.10 migracija sadrži after_sales_case_items
PASS  beta7.10 migracija sadrži after_sales_messages
PASS  beta7.10 migracija sadrži after_sales_attachments
PASS  beta7.10 migracija sadrži after_sales_status_history
PASS  beta7.10 ima tri postprodajne dozvole
PASS  beta7.10 pristup poštuje vlasnika dodeljenog admina i superadmin scope
PASS  beta7.10 slučaj zahteva isporučenu ili kompletiranu porudžbinu
PASS  beta7.10 čuva pogođene stavke snapshot i SLA rok
PASS  beta7.10 privatni prilozi proveravaju MIME veličinu i autorizaciju
PASS  beta7.10 javne i interne poruke su odvojene
PASS  beta7.10 statusni tok zahteva obrazloženje konačne odluke
PASS  beta7.10 automatizacija upozorava na probijene rokove slučaja
PASS  beta7.10 UI ima korisnički i administratorski postprodajni tok
PASS  beta7.10 doctor proverava šemu dozvole i SQL
PASS  beta7.10 feature test pokriva privatni prilog i obradu
PASS  beta7.10 privatni download zabranjuje browser cache
PASS  beta7.10 konkurentno zatvaranje ne propušta novu poruku
PASS  beta7.10 reopening zahteva razlog i čuva vreme prethodnog rešenja
PASS  beta7.10 nedodeljeni slučajevi obaveštavaju superadministratore
PASS  beta7.10 dashboard prikazuje aktivne probijene i waiting slučajeve
PASS  beta7.11 migracija sadrži after_sales_actions
PASS  beta7.11 migracija sadrži after_sales_action_items
PASS  beta7.11 migracija sadrži after_sales_action_id
PASS  beta7.11 migracija sadrži after_sales.execute
PASS  beta7.11 podržava četiri izvršne radnje
PASS  beta7.11 lager efekti su zaključani i idempotentni
PASS  beta7.11 refundacija je vezana za radnju i ograničena neto uplatom
PASS  beta7.11 slučaj čeka završetak aktivnih radnji
PASS  beta7.11 UI ima planiranje pokretanje izvršenje i otkazivanje
PASS  beta7.11 Gate i permission middleware štite izvršne kontrole
PASS  beta7.11 controller ima sve izvršne endpoint-e
PASS  beta7.11 automatizacija prati rok izvršne radnje
PASS  beta7.11 dashboard prikazuje radnje za izvršenje
PASS  beta7.11 testovi pokrivaju idempotentni lager povrat i refundaciju
PASS  beta7.12 migracija sadrži field_service_teams
PASS  beta7.12 migracija sadrži field_work_orders
PASS  beta7.12 migracija sadrži field_work_order_attachments
PASS  beta7.12 migracija sadrži field_operations.view
PASS  beta7.12 migracija sadrži field_operations.manage
PASS  beta7.12 fizičke radnje automatski dobijaju radni nalog
PASS  beta7.12 sprečava preklapanje termina iste ekipe
PASS  beta7.12 završetak zahteva dolazak na lokaciju
PASS  beta7.12 radni nalog čuva troškove kilometražu i privatne dokaze
PASS  beta7.12 UI ima kalendar ekipe i operativne statuse
PASS  beta7.12 rute i Gate štite terenske operacije
PASS  beta7.12 automatizacija prati neplanirane i probijene radne naloge
PASS  beta7.12 doctor proverava tabele dozvole rute i SQL
PASS  beta7.12 test pokriva auto nalog konflikt i on-site završetak
PASS  beta7.13 migracija sadrži service_part_suppliers
PASS  beta7.13 migracija sadrži service_parts
PASS  beta7.13 migracija sadrži field_work_order_parts
PASS  beta7.13 migracija sadrži service_part_movements
PASS  beta7.13 migracija sadrži service_part_purchase_requests
PASS  beta7.13 migracija sadrži service_part_purchase_request_items
PASS  beta7.13 migracija sadrži service_parts.view
PASS  beta7.13 migracija sadrži service_parts.manage
PASS  beta7.13 migracija sadrži service_parts.procurement
PASS  beta7.13 početno stanje ulazi u movement ledger
PASS  beta7.13 rezervacija ne umanjuje fizičko stanje
PASS  beta7.13 završetak skida stvarni utrošak i oslobađa ostatak
PASS  beta7.13 otkazivanje oslobađa sve rezervacije
PASS  beta7.13 kretanja servisnog lagera su idempotentna i ponovo proverena pod lockom
PASS  beta7.13 nacrt nabavke koristi konkurentno bezbedan privremeni broj
PASS  beta7.13 prijem nabavke računa ponderisanu prosečnu cenu
PASS  beta7.13 UI ima servisni lager dobavljače nabavku i utrošak
PASS  beta7.13 Gate i rute štite lager i nabavku
PASS  beta7.13 automatizacija prati nizak lager i kašnjenje nabavke
PASS  beta7.13 doctor proverava tabele dozvole rute i SQL
PASS  beta7.13 testovi pokrivaju ledger rezervaciju utrošak i ponderisanu cenu
PASS  beta7.13 UI ima responsive stilove servisnog lagera
PASS  beta7.14.1 migracija prvo obezbeđuje FK indeks
PASS  beta7.14 migracija uklanja unique order/type ograničenje
PASS  beta7.14 migracija uvodi revizije i vezu sa prethodnim dokumentom
PASS  beta7.14 servis vraća samo aktivan dokument ili izdaje novu reviziju
PASS  beta7.14 storniranje zahteva razlog i čuva audit podatak
PASS  beta7.14 model podržava supersedes relaciju
PASS  beta7.14 UI razlikuje aktivan dokument i novu reviziju
PASS  beta7.14 PDF prikazuje broj revizije
PASS  beta7.14 regresioni test pokriva ponovno izdavanje
PASS  beta7.15 migracija sadrži warranty_rules
PASS  beta7.15 migracija sadrži product_warranties
PASS  beta7.15 migracija sadrži warranty_maintenance_records
PASS  beta7.15 migracija sadrži warranties.view_own
PASS  beta7.15 migracija sadrži warranties.manage
PASS  beta7.15 pravila imaju product category global prioritet
PASS  beta7.15 kompletiranje automatski izdaje garanciju bez obaranja porudžbine
PASS  beta7.15 otkazivanje poništava aktivne garancije
PASS  beta7.15 GAR poslovni broj je registrovan
PASS  beta7.15 garancija čuva snapshot kupca artikla uslova i serijskih brojeva
PASS  beta7.15 preventivno održavanje generiše sledeći termin
PASS  beta7.15 zakazivanje ne menja vreme tokom provere datuma
PASS  beta7.15 backfill bira samo stavke bez garancije
PASS  beta7.15 PDF garantni list prikazuje ključne snapshot podatke
PASS  beta7.15 korisnički i administratorski prikazi postoje
PASS  beta7.15 rute Gates i administratorski scope štite garancije
PASS  beta7.15 automatizacija prati istek i održavanje
PASS  beta7.15 dashboard prikazuje garancije
PASS  beta7.15 doctor i backfill komande postoje
PASS  beta7.15 feature test pokriva automatsko izdavanje i prioritet pravila
PASS  beta7.15 warranty PDF smoke postoji
PASS  beta7.16 migracija uvodi outbox QR snapshot i dane garancije
PASS  beta7.16 migracija ima recovery putanju za delimičan MariaDB DDL
PASS  beta7.16 e-mail outbox ima dedupe intervale i pojedinačne primaoce
PASS  beta7.16 e-mail prima autor odgovorno lice i dodatne adrese
PASS  beta7.16 workflow šalje status tracking plaćanje i dokumente
PASS  beta7.16 dispatcher ima retry stuck recovery i zaštitu storniranog priloga
PASS  beta7.16 scheduler šalje outbox svake minute
PASS  beta7.16 admin podešava intervale događaje i dokumente
PASS  beta7.16 e-mail šablon ima događaje i bezbedan action link
PASS  beta7.16 NBS payload koristi zvanične oznake i RSD zarez
PASS  beta7.16 NBS servis koristi zvanični HTTPS endpoint i čuva privatni PNG snapshot
PASS  beta7.16 stornirani istorijski dokument ostaje pregledljiv bez ponovnog NBS poziva
PASS  beta7.16 finansijski dokument trazi NBS QR samo za pozitivan neplaceni saldo
PASS  beta7.16 PDF crta PNG bez GD i prikazuje NBS IPS QR oznaku
PASS  beta7.16 IPS QR smoke potvrđuje sliku oznaku i tačan RSD iznos
PASS  beta7.16 garancija podržava kombinaciju meseci i dana
PASS  beta7.16 admin može kreirati porudžbinu
PASS  beta7.16 doctor proverava outbox SMTP scheduler NBS i garancijske dane
PASS  beta7.16 feature test pokriva admin porudžbinu događaje NBS QR i dane garancije
PASS  beta7.17 migracija uvodi predmete rate i komunikaciju naplate
PASS  beta7.17 migracija je recovery-safe za delimičan DDL
PASS  beta7.17 servis automatski otvara zatvara i usklađuje predmete
PASS  beta7.17 rate se raspoređuju prema stvarno plaćenom iznosu
PASS  beta7.17 automatske opomene koriste faze dedupe i outbox
PASS  beta7.17 admin ima aging pregled plan i evidenciju komunikacije
PASS  beta7.17 podmeni se zatvara klikom van escape i izborom stavke
PASS  beta7.17 checkbox i radio imaju normalnu globalnu veličinu
PASS  beta7.17 doctor proverava šemu dozvolu i scheduler
PASS  beta7.17.1 permission seed je schema-aware
PASS  beta7.17.1 seeder ne zahteva permissions.updated_at
PASS  beta7.17.2 hover podmeni ima grace period i click pin
PASS  beta7.17.2 CSS premošćava razmak do podmenija
PASS  beta7.18 migracija uvodi strukturirane opcije i korelacije
PASS  beta7.18 migracija je recovery-safe za MariaDB
PASS  beta7.18 procesor ima porodicu i tačan model
PASS  beta7.18 brend filtrira samo sopstvene linije
PASS  beta7.18 generičke zavisnosti imaju server validaciju i zaštitu ciklusa
PASS  beta7.18 forma skriva nepovezane opcije i čuva detalj
PASS  beta7.18 kataloški filteri podržavaju select range boolean text i detalj
PASS  beta7.18 oba kataloga koriste korelisane filtere
PASS  beta7.18 doctor proverava procesor linije veze i tipove
PASS  beta7.18.1 forma artikla ne koristi nedostupni index filter servis
PASS  beta7.18.1 jedinstveni katalog dobija podatke za korelisane filtere
PASS  beta7.18.1 šifarnici dobijaju podatke za roditelje i mape zavisnosti
PASS  beta7.19 migracija uvodi šablone kompletnost i poreklo klona
PASS  beta7.19 migracija je recovery-safe i obračunava postojeći katalog
PASS  beta7.19 template servis podržava alias placeholdere
PASS  beta7.19 completeness servis vraća nepotpun aktivan artikal u nacrt
PASS  beta7.19 kloniranje čuva novi SKU i nulti lager
PASS  beta7.19 clone checkboxi eksplicitno šalju nulu
PASS  beta7.19 bulk zahteva pregled i blokira praznu operaciju
PASS  beta7.19 bulk promena brenda čisti neusklađenu liniju
PASS  beta7.19 preview naziva uklanja method spoof
PASS  beta7.19 doctor proverava šemu rute i kompletnost
PASS  beta7.19 feature test pokriva naziv klon i bulk
PASS  beta7.20 istorijska migracija ostaje sačuvana kao migration history
PASS  Product Variants forward decommission migracija postoji jednom
PASS  Product Variants decommission migracija ima recovery-safe rollback rekonstrukciju
PASS  Product Variants runtime klase su fizički uklonjene
PASS  Product Variants admin UI fajlovi su fizički uklonjeni
PASS  Porudžbine su product-only bez variant identiteta i snapshotova
PASS  Postprodaja garancija i stock movement su product-only
PASS  Inventory je product-only bez variants_enabled grane
PASS  Kataloški query filter i detalj su product-only
PASS  Product slike i model su product-only
PASS  Clone vise ne nudi niti obrađuje kopiranje varijanti
PASS  Product Variants Feature test sada proverava retired route i uklonjenu šemu
PASS  Product Variants UI contract sada zahteva potpuno uklonjen variant UI
PASS  Product Variants decommission smoke postoji kao završni regresioni guard
PASS  beta7.17 feature test pokriva dedupe rate zatvaranje i UI regresiju
PASS  beta7.21 migracija uvodi nabavne snapshotove i rasporede
PASS  beta7.21 migracija je recovery-safe i permission schema-aware
PASS  beta7.21 marža koristi snapshot i prikazuje pokrivenost troška
PASS  beta7.21 filteri važe za KPI trend i segmente
PASS  beta7.21 dashboard pokriva lager potraživanja postprodaju i tim
PASS  beta7.21 PDF upravljačkog izveštaja postoji
PASS  beta7.21 raspored ima retry dedupe i zasebne primaoce
PASS  beta7.21 ekran je bezbedan pre migracije
PASS  beta7.21 UI ima CSV PDF rasporede i cost coverage
PASS  beta7.22.1 management analytics koristi aktivnu temu bez belog fallback-a
PASS  beta7.22.1 CSS kompatibilni aliasi postoje
PASS  beta7.21 feature test pokriva ekran export i raspored
PASS  beta7.22 portal servis i fallback podaci postoje
PASS  beta7.22 portal objedinjuje porudžbine dokumente uplate garancije i servis
PASS  beta7.22 report grouping je kompatibilan sa ONLY_FULL_GROUP_BY
PASS  beta7.22 dashboard ima trend prioritete brze akcije i operativne module
PASS  beta7.22.1 portal doctor prosleđuje ViewErrorBag
PASS  beta7.22.1 layout bezbedno proverava errors bag
PASS  beta7.23 release-check komanda ima profile i kontrolisane režime
PASS  beta7.23 release registry ima quick standard i full profile
PASS  beta7.23 release plan ne dispatchuje poslovne akcije
PASS  beta7.23 release metadata i atomski JSON report postoje
PASS  beta7.23 release rezultat ima READY i NOT READY ugovor
PASS  beta7.23 smoke i dokumentacija postoje
PASS  beta7.23.1 catalog detail je product-only i nema retired variant Blade markere
PASS  beta7.23.1 catalog detail Blade direktive su izbalansirane
PASS  beta7.23.1 product-only detail Feature i smoke regresija postoje
PASS  beta7.23.1 health daje čitljive runtime remediation komande
PASS  beta7.23.2 detail doctor rešava controller zavisnosti kroz container
PASS  beta7.23.2 detail doctor nema direktan edit poziv sa jednim argumentom
PASS  beta7.23.2 detail doctor smoke i contract regresija postoje
PASS  beta7.24 migracija uvodi aktivacije sesije komunikaciju i order-link audit
PASS  beta7.24 aktivacioni token je hashiran jednokratan i vremenski ograničen
PASS  beta7.24 session registry koristi hash i podržava revoke
PASS  beta7.24 kupac vidi samo javne poruke a admin interne
PASS  beta7.24 portal rute aktivacija i admin centar postoje
PASS  beta7.24 komunikacija razdvaja public i internal
PASS  beta7.24 smoke i PHPUnit regresije postoje
PASS  beta7.24 maintenance čisti tokene i stare session evidencije
PASS  beta7.24 reinvite ne deaktivira aktivnog kupca i aktivacija nije cache-ovana
PASS  beta7.24.1 management repair obrađuje missing snapshotove
PASS  beta7.24.1 repair ne prepisuje kompletne snapshotove
PASS  beta7.24.1 repair je transakcioni i koristi row lock
PASS  beta7.24.1 kandidati imaju transparentan product-only izvor
PASS  beta7.24.1 ručna finansijska promena zahteva razlog i audit
PASS  beta7.24.1 audit/repair komanda i regresije postoje
PASS  rc1 profil sadrzi final hardening provere
PASS  rc1 security doctor proverava production debug HTTPS session i public fajlove
PASS  rc1 migration doctor proverava pending SQL mode i foreign keys
PASS  rc1 access doctor proverava route permission i superadmin
PASS  rc1 release integrity proverava SHA-256 i path traversal
PASS  rc1 backup verify je read-only i proverava SQL gzip i file hash
PASS  rc1 smoke i contract regresije postoje
PASS  rc1 nema novu migration datoteku
PASS  stable profil je identican potvrdenom rc profilu
PASS  stable smoke i contract regresije postoje
PASS  stable početna je univerzalni dashboard sa integrisanim korisničkim centrom
PASS  stable nema zasebnu Moj portal stranicu ni stavku menija
PASS  stable nema novu migration datoteku
PASS  v2.1.2 obaveštenja o novom artiklu su opt-in i koriste outbox
PASS  v2.1.2 novi artikal se šalje aktivnim registrovanim korisnicima bez duplikata
PASS  v2.1.2 mail podešavanja i šablon podržavaju nove artikle
PASS  v2.1.2 product announcement regresije postoje
PASS  v2.1.2 nema novu migration datoteku
PASS  APP_ENV production
PASS  Redis nije obavezan za database queue
PASS  file session/cache/limiter i database queue
PASS  secret vrednosti su prazne
PASS  import ne upisuje legacy konekciju
INFO  ZIP hygiene provere su preskočene na instaliranoj aplikaciji; za raspakovani sanitized ZIP koristi --package.
PASS  v2.1.3 tipovi proizvoda imaju posebne stranice i Drag & Drop
PASS  v2.1.3 specifikaciona polja mogu trajno da se obrišu
PASS  v2.1.3 tip automatski određuje kategoriju
PASS  v2.1.3 diskovi imaju pojedinačne celobrojne GB kapacitete
PASS  v2.1.3 catalog settings doctor postoji
PASS  v2.1.3 grana ima tri kontrolisane migration datoteke
PASS  v2.1.3.3 ProductRequest zadržava validan SKU regex delimiter
PASS  v2.1.3.3 ProductVariantRequest je retired a ProductRequest zadržava validan SKU regex
PASS  v2.1.3.3 migracija povezuje listu diskova i izvedeni ukupni kapacitet
PASS  v2.1.3.3 stari kapacitet se bezbedno prenosi na prvi disk
PASS  v2.1.3.3 backend ne veruje ručnom ukupnom zbiru
PASS  v2.1.3.3 ukupni kapacitet je ispod diskova i readonly
PASS  v2.1.3.3 frontend sabira diskove i čuva početni legacy zbir
PASS  v2.1.3.3 product-only storage model zadržava izvedeni zbir bez variant servisa
PASS  v2.1.3.3 storage smoke i contract test postoje
PASS  v2.1.4 migracija dodaje model proizvoda i usklađuje šablone
PASS  v2.1.4 model se validira čuva i koristi u nazivu
PASS  v2.1.4 forma ima model proizvoda posle linije
PASS  v2.1.4 trajno brisanje ima SKU potvrdu i izbor brisanja slika
PASS  v2.1.4 poslovna istorija blokira destruktivno brisanje
PASS  v2.1.4 semantički sistem tastera pokriva sve uloge
PASS  v2.1.4 route i stable doctor postoje
PASS  v2.1.4 smoke i contract test postoje
PASS  v2.1.4.1 controller priprema i prosledjuje orderedFields
PASS  v2.1.4.1 Blade bezbedno inicijalizuje orderedFields
PASS  v2.1.4.1 doctor renderuje formulare svih tipova
PASS  v2.1.4.1 smoke i contract test postoje
PASS  v2.1.5 globalni UX runtime štiti submit i nesačuvane izmene
PASS  v2.1.5 mobilni action dock koristi originalni submit
PASS  v2.1.5 validacija i accessibility markeri postoje
PASS  v2.1.5 dugi formulari su eksplicitno označeni
PASS  v2.1.5 sistemske error stranice postoje
PASS  v2.1.5 migracija koristi postojeće snaga-napajanja polje
PASS  v2.1.5 migracija postavlja napajanje u sredinu
PASS  v2.1.5 doctor proverava Blade, route akcije i napajanje
PASS  v2.1.5 stable release koristi render i repair
PASS  v2.1.5 smoke i contract test postoje
PASS  v2.1.6 migracija kreira snapshot istoriju i ciljane indekse
PASS  v2.1.6 Data Quality audit pokriva product-only katalog slike i specifikacije
PASS  v2.1.6 repair je nedestruktivan i preračunava izvedene vrednosti
PASS  v2.1.6 performance doctor proverava indekse cache i SQL pragove
PASS  v2.1.6 dashboard kešira schema metadata po requestu
PASS  v2.1.6 Data Quality Center rute i prikaz postoje
PASS  v2.1.6 katalog ima quality filtere
PASS  v2.1.6 doctor renderuje centar i pokreće performance audit
PASS  v2.1.6 Stable release uključuje render repair i strict
PASS  v2.1.6 smoke i contract test postoje
PASS  v2.2.0 bootstrap device catalog order i notification rute postoje
PASS  v2.2.0 bootstrap vraća permissions features i app policy
PASS  v2.2.0 uređaji deduplikuju push tokene i podržavaju opoziv
PASS  v2.2.0 API greške imaju stabilan envelope
PASS  v2.2.0 OpenAPI i Stable doctor su povezani

Ukupno: 983, neuspešno: 0
CMS_STATIC_CHECK=PASS_983_OF_983

> ald1n-mobile@1.0.0 validate
> node scripts/validate-project.mjs

PASS package.json postoji.
PASS app.config.js postoji.
PASS eas.json postoji.
PASS .env.example postoji.
PASS assets/icon.png postoji.
PASS assets/adaptive-icon.png postoji.
PASS assets/splash-icon.png postoji.
PASS src/app/_layout.tsx postoji.
PASS src/app/(auth)/login.tsx postoji.
PASS src/app/(auth)/forgot-password.tsx postoji.
PASS src/app/(auth)/reset-password.tsx postoji.
PASS src/app/(auth)/activate-account.tsx postoji.
PASS src/app/(app)/(tabs)/home.tsx postoji.
PASS src/app/(app)/(tabs)/catalog.tsx postoji.
PASS src/app/(app)/(tabs)/orders.tsx postoji.
PASS src/app/(app)/(tabs)/notifications.tsx postoji.
PASS src/app/(app)/(tabs)/account.tsx postoji.
PASS src/app/(app)/product/[slug].tsx postoji.
PASS src/app/(app)/order/[id].tsx postoji.
PASS src/app/(app)/devices.tsx postoji.
PASS src/app/(app)/sessions.tsx postoji.
PASS src/app/(app)/portal/messages/index.tsx postoji.
PASS src/app/(app)/portal/messages/[id].tsx postoji.
PASS src/app/(app)/admin/customer-portal/index.tsx postoji.
PASS src/app/(app)/admin/customer-portal/[userId].tsx postoji.
PASS src/app/(app)/admin/customer-portal/conversations/[id].tsx postoji.
PASS src/app/(app)/cart.tsx postoji.
PASS src/app/(app)/checkout.tsx postoji.
PASS src/app/(app)/notification-settings.tsx postoji.
PASS src/app/(app)/after-sales/index.tsx postoji.
PASS src/app/(app)/after-sales/[id].tsx postoji.
PASS src/app/(app)/after-sales/create/[orderId].tsx postoji.
PASS src/app/(app)/warranties/index.tsx postoji.
PASS src/app/(app)/warranties/[id].tsx postoji.
PASS src/app/(app)/commissions/index.tsx postoji.
PASS src/app/(app)/commissions/[id].tsx postoji.
PASS src/app/(app)/assigned-orders/index.tsx postoji.
PASS src/app/(app)/assigned-orders/[id].tsx postoji.
PASS src/features/warranties/warranty-pdf.ts postoji.
PASS src/features/orders/order-post-create-files.ts postoji.
PASS src/features/after-sales/attachment-picker.ts postoji.
PASS src/features/after-sales/attachment-download.ts postoji.
PASS src/lib/api/client.ts postoji.
PASS src/lib/api/endpoints.ts postoji.
PASS src/features/auth/auth-provider.tsx postoji.
PASS src/features/auth/google-auth.ts postoji.
PASS src/features/device/device-registrar.tsx postoji.
PASS src/features/cart/cart-provider.tsx postoji.
PASS src/features/notifications/push-service.ts postoji.
PASS src/features/notifications/push-notification-bridge.tsx postoji.
PASS docs/openapi.yaml postoji.
PASS src/features/admin/dictionary-admin-api.ts postoji.
PASS src/app/(app)/admin/catalog/dictionaries/index.tsx postoji.
PASS src/app/(app)/admin/catalog/dictionaries/[resource].tsx postoji.
PASS src/app/(app)/admin/catalog/dictionaries/product-types/[id].tsx postoji.
PASS src/features/admin/order-documents-admin.tsx postoji.
PASS src/features/admin/order-document-files.ts postoji.
PASS src/features/admin/product-deletion-admin.tsx postoji.
PASS src/features/admin/audit-admin-export.ts postoji.
PASS src/features/admin/module-settings-admin-api.ts postoji.
PASS src/features/portal/portal-api.ts postoji.
PASS src/features/admin/customer-portal-admin-api.ts postoji.
PASS src/features/admin/user-groups-admin-api.ts postoji.
PASS src/app/(app)/admin/user-groups/index.tsx postoji.
PASS src/features/admin/catalog-advanced-admin-api.ts postoji.
PASS src/features/admin/catalog-advanced-product-actions.tsx postoji.
PASS src/features/catalog/catalog-product-edit-handoff.ts postoji.
PASS src/features/admin/data-quality-admin-api.ts postoji.
PASS src/features/admin/data-quality-admin-export.ts postoji.
PASS src/app/(app)/admin/catalog/[id]/clone.tsx postoji.
PASS src/app/(app)/admin/catalog/bulk/index.tsx postoji.
PASS src/app/(app)/admin/catalog/data-quality/index.tsx postoji.
PASS src/features/admin/order-archive-admin.tsx postoji.
PASS src/app/(app)/admin/orders/archived.tsx postoji.
PASS src/features/admin/operational-reports-admin-export.ts postoji.
PASS src/features/admin/global-search-admin-api.ts postoji.
PASS src/app/(app)/admin/search.tsx postoji.
PASS src/app/(app)/admin/settings/modules/index.tsx postoji.
PASS tamagui.config.ts postoji.
PASS src/design/ald1n-tokens.generated.ts postoji.
PASS Generated design token fajlovi su sinhronizovani sa canonical JSON source-om.
PASS Tamagui onBrand koristi canonical onPrimary semantic token.
PASS package.json je validan JSON.
PASS eas.json je validan JSON.
PASS Expo SDK 57 verzija prati aktuelni SDK 57 patch baseline.
PASS React Native verzija prati Expo SDK 57 template.
PASS Expo Router verzija je zaključana.
PASS Expo development client je uključen.
PASS SecureStore zavisnost postoji.
PASS TanStack Query zavisnost postoji.
PASS Minimalna Node.js verzija odgovara SDK 57 zahtevu.
PASS Aplikaciona package verzija je 1.0.0.
PASS package-lock release verzija je 1.0.0.
PASS expo-notifications prati SDK 57 preporučenu verziju.
PASS Expo Symbols je uključen za native Material/SF ikonice.
PASS Moderni Google Credential Manager bridge je uključen.
PASS Nitro Modules runtime je pinovan.
PASS Tamagui 2 runtime je pinovan.
PASS Tamagui Config v5 paket je pinovan.
PASS Tamagui Reanimated driver je pinovan.
PASS Expo System UI prati SDK 57 preporucenu verziju.
PASS Expo Status Bar prati SDK 57 preporucenu verziju.
PASS Expo FileSystem je direktno zakljucan za after-sales izbor priloga.
PASS Expo Sharing je zakljucan za bezbedno otvaranje privatnih after-sales priloga.
PASS v0.8 Expo compatibility matrix zaključava expo na ~57.0.17.
PASS v0.8 Expo compatibility matrix zaključava expo-constants na ~57.0.15.
PASS v0.8 Expo compatibility matrix zaključava expo-crypto na ~57.0.2.
PASS v0.8 Expo compatibility matrix zaključava expo-dev-client na ~57.0.16.
PASS v0.8 Expo compatibility matrix zaključava expo-file-system na ~57.0.6.
PASS v0.8 Expo compatibility matrix zaključava expo-linking na ~57.0.8.
PASS v0.8 Expo compatibility matrix zaključava expo-notifications na ~57.0.15.
PASS v0.8 Expo compatibility matrix zaključava expo-router na ~57.0.17.
PASS v0.8 Expo compatibility matrix zaključava expo-sharing na ~57.0.16.
PASS v0.8 Expo compatibility matrix zaključava expo-secure-store na ~57.0.2.
PASS v0.8 Expo compatibility matrix zaključava expo-system-ui na ~57.0.3.
PASS v0.8 Expo compatibility matrix zaključava expo-splash-screen na ~57.0.8.
PASS v0.8 Expo compatibility matrix zaključava expo-updates na ~57.0.18.
PASS Static colors consumeri su uklonjeni iz aplikacionog source-a.
PASS Legacy colors.* usage ne postoji van RN theme adaptera.
PASS Unsafe as never / as unknown as castovi ne postoje u source-u.
PASS 178 TypeScript/TSX fajlova prolazi sintaksnu proveru.
PASS app.config.ts prolazi TypeScript sintaksnu proveru.
PASS 1294 lokalnih @/ importa je razrešeno.
PASS Bearer token header je implementiran.
PASS Globalni 401 logout je implementiran.
PASS Request ID je sačuvan u API grešci.
PASS API timeout je implementiran.
PASS Secure auth lifecycle je implementiran.
PASS Neuspešan bootstrap posle logina vraća aplikaciju u bezbedno anonymous stanje.
PASS API klijent koristi auth/token ugovor.
PASS API klijent koristi auth/google ugovor.
PASS API klijent koristi bootstrap ugovor.
PASS API klijent koristi catalog/filters ugovor.
PASS API klijent koristi products ugovor.
PASS API klijent koristi orders/options ugovor.
PASS API klijent koristi Idempotency-Key ugovor.
PASS API klijent koristi orders ugovor.
PASS API klijent koristi notifications ugovor.
PASS API klijent koristi devices ugovor.
PASS API klijent koristi me/notification-preferences ugovor.
PASS API klijent koristi PATCH ugovor.
PASS Order API client exposes Assigned-to-me list/detail contract.
PASS Assigned Orders client reuses the canonical Order contract for list/detail.
PASS Assigned Orders customer/mobile contract adds discovery only and no workflow mutation methods.
PASS Order post-create API types cover summary, payment ledger and proof upload.
PASS Order API client covers post-create summary, proof upload and secure binary path contracts.
PASS Order post-create Mobile types do not expose internal actor IDs or storage paths.
PASS Order customer API client does not expose admin payment or delivery workflow actions.
PASS Order private-file paths are prepared for the existing authenticated apiDownload transport.
PASS v0.9 Orders ekran otvara Dodeljene porudžbine samo korisniku sa orders.manage dozvolom.
PASS Assigned Orders lista koristi dedicated API, permission gate, detail rutu i server pagination.
PASS Assigned Order detalj koristi dedicated detail API i prikazuje canonical Order customer/assignment podatke.
PASS Assigned Orders UI ostaje read-only i ne izlaže owner post-create ili admin workflow mutacije/interne storage podatke.
PASS Order detalj prikazuje server-driven payment/document/delivery post-create summary.
PASS Order detalj šalje payment proof samo kada server capability to dozvoli i koristi server file limite.
PASS Order payment-proof picker koristi postojeći Expo FileSystem i server MIME/extension/size limite.
PASS Order privatni fajlovi koriste Bearer binary transport i provereni privatni cache.
PASS Order PDF/proof helper validira PDF i otvara privatne fajlove kroz postojeći Expo Sharing flow.
PASS Order private-file helper prihvata samo tipizovane customer API path buildere.
PASS Order detalj ne otvara privatne URL-ove direktno već koristi secure Bearer/cache/share helper.
PASS Post-create UI čuva postojeći customer cancel i After-sales create tok.
PASS Order customer post-create UI/helper ne izlažu admin akcije, actor ID-jeve ili storage putanje.
PASS API klijent sadrži after-sales ugovor.
PASS After-sales lista koristi API, dozvolu i detalj rutu.
PASS After-sales detalj prikazuje slučaj, radnje i javnu komunikaciju.
PASS After-sales detalj podržava slanje javne poruke samo kada je komunikacija otvorena.
PASS After-sales create ekran koristi server options, create endpoint, create dozvolu i izabrane stavke.
PASS After-sales attachment picker koristi Expo FileSystem i server limite bez novog picker paketa.
PASS API klijent podržava autentifikovan binary download uz postojeći Bearer lifecycle.
PASS After-sales privatni prilog se preuzima samo kroz očekivanu API putanju i čuva u provereni privatni cache.
PASS After-sales privatni prilog koristi Expo Sharing tek nakon provere platforme i dostupnosti sistema.
PASS After-sales detalj otvara privatne priloge kroz bezbedan Bearer download umesto direktnog privatnog URL-a.
PASS After-sales work-order tip izlaže javne field-work priloge.
PASS Secure attachment helper dozvoljava samo očekivanu field-work Bearer putanju i odvaja cache namespace.
PASS After-sales detalj prikazuje javnu terensku dokumentaciju i otvara je kroz postojeći secure flow.
PASS After-sales create ekran bira, prikazuje i šalje priloge prema server limitima.
PASS After-sales detail tip izlaže server-driven limite.
PASS After-sales message composer bira, prikazuje i šalje priloge prema server limitima.
PASS Order detalj otvara create-from-order ekran samo korisniku sa after_sales.create dozvolom.
PASS v0.9 After-sales prečica je uklonjena iz Porudžbina i premeštena u Moje aktivnosti.
PASS Warranty API tipovi pokrivaju listu, detalj i maintenance timeline.
PASS API klijent sadrži Warranty list/detail ugovor.
PASS Warranty lista koristi API, permission gate, detail rutu i maintenance summary.
PASS Warranty detalj prikazuje customer-safe garantni list, uslove, serijske brojeve, status i maintenance timeline.
PASS Warranty PDF se preuzima Bearer transportom, validira kao PDF i čuva u provereni privatni cache.
PASS Warranty PDF koristi postojeći Expo Sharing tek nakon platform/device provere.
PASS Warranty detalj otvara privatni PDF kroz bezbedan Bearer/cache/share flow bez direktnog URL-a.
PASS v0.9 Warranty prečica je uklonjena iz Porudžbina i premeštena u Moje aktivnosti.
PASS Commission API tipovi pokrivaju customer list/detail, statuse, summary i pagination ugovor.
PASS API klijent sadrži Commission list/filter/detail ugovor.
PASS Commission Mobile contract ne izlaže admin actor/history/payment-batch interne identifikatore.
PASS Commission lista koristi customer permission, q/status/date filtere, server summary, pagination i detail rutu.
PASS Commission detalj prikazuje customer-safe obračun, status, napomenu, isplatu i link ka porudžbini.
PASS v0.9 Commission prečica je uklonjena iz Porudžbina i premeštena u Moje aktivnosti.
PASS Commission customer UI ne izlaže admin/interne workflow identifikatore ili akcije.
PASS Lokalna korpa koristi samo proizvod i količinu; variant identitet je dekomisioniran.
PASS Korpa se čisti pri odjavi/promeni korisnika.
PASS Mobile API tipovi više ne izlažu Product Variants.
PASS Mobile Product detalj više nema variant izbor.
PASS Mobile checkout šalje samo product_id i quantity.
PASS Admin After-sales Mobile contract više ne izlaže product_variant_id.
PASS Checkout čuva stabilan idempotency ključ za retry istog payload-a.
PASS Checkout podržava uslovni izbor računa za bank transfer.
PASS v0.8 Mobile API tipovi pokrivaju Odloženo plaćanje i datum dospeća.
PASS v0.8 Checkout prikazuje i šalje datum dospeća samo za Odloženo plaćanje.
PASS Device heartbeat više ne gasi push registraciju pri svakom startu.
PASS Android kanal se kreira pre Expo push tokena.
PASS Expo push token koristi EAS projectId.
PASS Push token se registruje kao Expo device token.
PASS Push token se ne loguje u klijentu.
PASS Foreground i tap push listeneri su implementirani.
PASS Cold-start notification response se čisti nakon obrade.
PASS Push order deep link vodi na detalj porudžbine.
PASS Notification settings uređuju push i poslovne kategorije.
PASS Notification settings podržavaju per-device push uključivanje i isključivanje.
PASS Account ekran podrzava izmenu profila i lokalno osvezavanje bootstrap korisnika.
PASS Account ekran podrzava promenu lozinke i obaveznu ponovnu prijavu.
PASS Account ekran zahteva najmanje 12 znakova za novu lozinku.
PASS Account ekran proverava potvrdu nove lozinke.
PASS API klijent koristi PATCH /me za profil.
PASS API klijent koristi PUT /me/password za lozinku.
PASS Google Sign-In koristi web client ID iz google-services.json i vraća ID token backendu.
PASS Google login ima saved-account, registration/account-picker i explicit fallback tok.
PASS Google Sign-In dugme prati aktivnu light/dark temu.
PASS Bottom navigation ima Material 3 tonalni aktivni indikator.
PASS v1.0 Bottom navigation aktivni TAB koristi puni tonalni pill indikator za ikonicu i naziv.
PASS Tab badge koristi semantic danger/onDanger foreground par.
PASS UI koristi native Expo Symbols umesto tekstualnih pseudo-ikonica.
PASS Canonical packages/api-contract/openapi.yaml postoji.
PASS Mobile OpenAPI kopija odgovara canonical packages/api-contract/openapi.yaml.
PASS CMS OpenAPI kopija postoji.
PASS CMS OpenAPI kopija odgovara canonical packages/api-contract/openapi.yaml.
PASS OpenAPI documents Assigned-to-me list/detail routes.
PASS Assigned Orders OpenAPI documents permission denial and strict detail not-found behavior.
PASS Assigned Orders OpenAPI contains no workflow mutation operations.
PASS OpenAPI contains Order post-create route /api/v1/orders/{order}/post-create:.
PASS OpenAPI contains Order post-create route /api/v1/orders/{order}/payments/proof:.
PASS OpenAPI contains Order post-create route /api/v1/orders/{order}/payments/{payment}/proof:.
PASS OpenAPI contains Order post-create route /api/v1/orders/{order}/documents/confirmation.pdf:.
PASS OpenAPI contains Order post-create route /api/v1/orders/{order}/documents/{document}.pdf:.
PASS OpenAPI contains Order post-create route /api/v1/orders/{order}/delivery-proof:.
PASS OpenAPI contains OrderPrivateFile: schema.
PASS OpenAPI contains OrderPaymentLedgerEntry: schema.
PASS OpenAPI contains OrderDocumentSummary: schema.
PASS OpenAPI contains OrderDeliverySummary: schema.
PASS OpenAPI contains OrderBankTransferSnapshot: schema.
PASS OpenAPI contains OrderPostCreateCapabilities: schema.
PASS OpenAPI contains OrderPaymentProofLimits: schema.
PASS OpenAPI contains OrderPostCreate: schema.
PASS Order post-create OpenAPI covers proof upload, binary downloads and private no-store cache policy.
PASS Order post-create OpenAPI does not expose internal actor/storage fields or admin workflow actions.
PASS OpenAPI dokumentuje Commission list/filter/detail, summary i pagination ugovor.
PASS Commission OpenAPI customer ugovor ne izlaže admin/interne identifikatore.
PASS OpenAPI dokumentuje Warranty list/detail i maintenance schema ugovor.
PASS OpenAPI dokumentuje privatni Warranty PDF Bearer download ugovor.
PASS OpenAPI AfterSalesCase detalj izlaže server-driven limite za poruke i priloge.
PASS OpenAPI work-order schema izlaže javne field-work priloge.
PASS OpenAPI field-work attachment ruta dokumentuje Bearer download ugovor.
PASS OpenAPI kopija sadrži /auth/token.
PASS OpenAPI kopija sadrži /auth/google.
PASS OpenAPI kopija sadrži /bootstrap.
PASS OpenAPI kopija sadrži /catalog/filters.
PASS OpenAPI kopija sadrži /products.
PASS OpenAPI kopija sadrži /orders/options.
PASS OpenAPI kopija sadrži Idempotency-Key.
PASS OpenAPI kopija sadrži /orders.
PASS OpenAPI kopija sadrži /notifications.
PASS OpenAPI kopija sadrži /devices.
PASS Deep-link scheme je postavljen.
PASS Android/iOS identifikatori su postavljeni.
PASS Expo Router typed routes su uključene.
PASS Dinamički EAS project ID je podržan.
PASS Expo app verzija je 1.0.0.
PASS Expo display naziv je Ald1n CMS bez Preview suffixa.
PASS Ald1n V2 logo je canonical icon/adaptive/splash/favicon asset.
PASS App runtime version fallback je 1.0.0.
PASS Account version fallback je 1.0.0.
PASS Android config podržava Firebase google-services.json kada postoji.
PASS App config uključuje Google Sign-In plugin kada je Firebase config prisutan.
PASS Expo userInterfaceStyle prati sistemsku light/dark temu.
PASS App theme mode je zakljucan na system.
PASS App theme resolver koristi React Native system color scheme.
PASS RN theme adapter koristi canonical onDanger semantic token.
PASS TamaguiProvider je povezan na root aplikacije.
PASS Root Tamagui, StatusBar i navigation background prate isti resolved scheme.
PASS Tamagui Config v5 i Reanimated driver su aktivni.
PASS Ald1n Light/Dark Tamagui palette su povezane.
PASS Tamagui onDanger koristi canonical onDanger semantic token.
PASS Product detail omogućava kopiranje ručno unetog opisa na Android/iOS.
PASS Mobile ima SDK 57 expo-clipboard zavisnost za kopiranje opisa.
PASS Admin Product Create ekran koristi catalog.manage_products i canonical admin catalog API.
PASS Admin Product Create prikazuje server validation grešku i posle uspeha otvara novi artikal.
PASS SelectSheet primitive postoji bez dodatnog native dependency-ja.
PASS API klijent sadrži Admin Catalog options/create ugovor.
PASS Mobile tipovi pokrivaju Admin Product Create metadata/input/response.
PASS Home prikazuje Dodaj artikal samo korisniku sa catalog.manage_products dozvolom.
PASS OpenAPI dokumentuje Admin Catalog options i product create rute.
PASS Admin Product Create renderuje dinamičke specifikacije, zavisne select opcije i detaljna polja.
PASS Admin Product Create fotografije su permission-gated i šalju se kroz canonical image API.
PASS Product image picker koristi postojeći Expo FileSystem i server-driven limite bez novog native dependency-ja.
PASS API klijent podržava multipart upload slika posle kreiranja artikla.
PASS Mobile tipovi pokrivaju dinamičke specifikacije, image limite i storage contract za sledeći specijalizovani korak.
PASS OpenAPI dokumentuje napredne spec metadata podatke i multipart product-image upload.
PASS Admin Product Create ima specijalizovani multi-disk repeater i skriva izvedeni total iz standardnih polja.
PASS Storage repeater šalje canonical specs/spec_lists/spec_capacities/spec_structured payload bez ručnog derived total-a.
PASS Mobile tipovi izlažu server-driven storage repeater i read-only derived metadata.
PASS OpenAPI dokumentuje server-driven storage repeater metadata i derived total polje.
PASS v0.8 Product Edit izlaže SuperAdmin Evidentiraj prodaju direktno sa artikla.
PASS v0.8 Direct Sale ekran koristi server options, stable idempotency i unrestricted tap contract.
PASS v0.8 Admin Catalog API klijent pokriva Direct Sale options i record ugovor.
PASS OpenAPI dokumentuje SuperAdmin Direct Sale options/record i idempotency ugovor.
PASS v1.0 Direct Sale dozvoljava cenu iznad kataloške uz pozitivnu cenu i SuperAdmin workflow.
PASS P2 Admin hub koristi centralni access helper, API i query-key foundation.
PASS P2 Admin access helper centralizuje administratorske dozvole i admin/superadmin role fallback.
PASS P2 Admin API helper koristi canonical /api/v1/admin foundation endpoint.
PASS P2 Admin query-key family je centralizovana.
PASS Home prikazuje centralni Admin entry kroz isti access helper.
PASS OpenAPI dokumentuje P2 Admin foundation endpoint i schema ugovor.
PASS P2 FilterBar ima chips, active count i clear contract.
PASS P2 DateTimeField je dependency-free kontrolisani date/datetime input.
PASS P2 MoneyField centralizuje decimalni unos i currency prikaz.
PASS P2 AsyncLookup je server-query friendly lookup bez duplog cache-a.
PASS P2 DataList je mobile-first virtualizovana lista sa refresh i empty state contractom.
PASS P2 ActionSheet koristi dependency-free Modal i aktuelni RN absoluteFill API.
PASS P2 ConfirmAction reuse-uje ActionSheet i odvaja confirm/cancel tok.
PASS P2 StatusTimeline ima reusable server-driven timeline contract.
PASS P2 postojeći SelectSheet i AppFeedback ostaju očuvani.
PASS P3 Admin Commissions API klijent pokriva list/detail/status/bulk-pay ugovor.
PASS P3 Admin Commissions CSV/PDF koristi relativnu API putanju i postojeći Bearer binary/cache/share flow.
PASS P3 Admin Commissions lista ima permission gate, filtere, bulk-pay i izvoze.
PASS P3 Admin Commissions detalj koristi server-driven prelaze i shared timeline.
PASS P3 Admin hub izlaže Provizije samo commissions.manage korisniku.
PASS P3 Admin Commissions query keys su centralizovani.
PASS OpenAPI dokumentuje kompletan P3 Admin Commissions route surface.
PASS OpenAPI dokumentuje P3 Admin Commissions schema ugovor.
PASS P3 Admin Warranties API klijent pokriva list/detail/update/void/maintenance ugovor.
PASS P3 Admin Warranties lista ima permission gate, filtere, statistiku i detail rutu.
PASS P3 Admin Warranties detalj koristi server-side warranty i maintenance mutacije.
PASS P3 Admin hub izlaže Garancije samo warranties.manage korisniku.
PASS P3 Admin Warranties query keys su centralizovani.
PASS OpenAPI dokumentuje P3 Admin Warranties core route i schema ugovor.
PASS P3 Admin Warranties 2E zaključava rules/backfill i relativni Admin PDF API ugovor.
PASS P3 Admin Warranties 2E zaključava Rules UI i Backfill tok.
PASS P3 Admin Warranties 2E zaključava Rules navigaciju i Admin PDF UI entry.
PASS P3 Admin Warranties 2E zaključava secure relativni Admin PDF Bearer/cache/share flow.
PASS OpenAPI dokumentuje kompletan P3 Admin Warranties Rules/Backfill/Admin PDF ugovor.
PASS P3 Admin Reports 2G zaključava read/schedule Mobile API ugovor i relativne Admin putanje.
PASS P3 Admin Reports 2G zaključava secure CSV/PDF Bearer/cache/share export tok.
PASS P3 Admin Reports 2G zaključava management dashboard, permission gate i schedule manager UI.
PASS P3 Admin Reports 2G zaključava centralizovane Reports query-key ugovore.
PASS OpenAPI dokumentuje kompletan P3 Admin Reports read/export/schedule ugovor od 10 operacija.
PASS P3/v1.0 Admin System Health koristi relativni API ugovor i izlaže run/backup/prune mutacije kroz canonical servisni tok.
PASS P3 Admin System Health 2C zaključava centralizovani System Health query key.
PASS P3/v1.0 Admin System Health UI ostaje permission-gated i dodaje snapshot, backup, retention, backup istoriju i security događaje.
PASS v0.9 Admin Hub drži System Health u grupi Sistem samo kroz system.health dozvolu.
PASS OpenAPI dokumentuje puni v1.0 Admin System Health GET/run/backup/prune ugovor.
PASS P3/v1.0 Admin Audit zaključava relativni read-only list/detail/CSV Mobile API ugovor bez raw user_agent/context_json polja.
PASS v1.0 AUDIT-01 CSV koristi postojeći Bearer binary transport, privatni cache i Expo Sharing bez paralelnog fetch toka.
PASS P3 Admin Audit 2C zakljucava centralizovane Audit list/detail query key ugovore.
PASS P3/v1.0 Admin Audit zaključava security.view list/filter/pagination/refetch UI i server-driven audit.export CSV akciju.
PASS P3 Admin Audit 2C zakljucava permission-gated safe detail UI i server-driven read-only capabilities.
PASS P3 Admin Audit 2C zakljucava Admin hub ulaz samo za security.view.
PASS OpenAPI dokumentuje kompletan AUDIT-01 read/filter/detail + sanitizovani CSV export ugovor bez mutacija.
PASS v1.0 SET-01 Mobile API koristi relativni canonical GET/PUT module settings ugovor.
PASS v1.0 SET-01 ekran je SuperAdmin-only, server-driven i osvežava bootstrap/foundation bez destruktivnog ponašanja.
PASS v1.0 SET-01 Admin Hub poštuje server module visibility i izlaže Moduli sistema u organizovanoj Sistem grupi.
PASS v1.0 SET-01 TanStack query key je centralizovan.
PASS OpenAPI dokumentuje kompletan SET-01 read/update ugovor i SuperAdmin permission granicu.
PASS v1.0 AUTH-02/AUTH-03 Mobile API koristi guest recovery/activation ugovor bez paralelnog token sistema.
PASS v1.0 AUTH-02/AUTH-03 Mobile UI pokriva forgot/reset/activation i prihvata 80-char CMS recovery token.
PASS v1.0 ACCOUNT-02 Mobile UI pokriva aktivne API/web prijave, pojedinačni revoke i revoke-others uz current-session zaštitu.
PASS OpenAPI dokumentuje kompletan AUTH-02 + AUTH-03 + ACCOUNT-02 mobile parity ugovor.
PASS Product image upload koristi eksplicitni Expo fetch transport sa postojecim auth/error lifecycle-om.
PASS Product image multipart koristi pravi Expo File umesto legacy uri/name/type pseudo-fajla.
PASS Product image multipart ne postavlja rucno Content-Type boundary.
PASS Product image picker prihvata Android image provider fajl bez ekstenzije kada je MIME dozvoljen, uz zadrzan MIME/extension guard za ostale fajlove.
PASS iOS Google Sign-In koristi canonical GoogleService-Info.plist kroz Expo i Nitro config plugin.
PASS iOS GoogleService-Info.plist sadrži preview bundle, iOS OAuth, reversed scheme i web client ID za autoDetect.
PASS iOS koristi zaseban 1024x1024 opaque RGB app icon bez alpha/tRNS transparentnosti.
PASS v0.9 Home izlaže Moje provizije kroz Moje aktivnosti i view-own permission model.
PASS v0.9 Home zadržava Brze akcije pre sekcije Moje aktivnosti.
PASS v0.9 Admin Hub drži Provizije u grupisanoj sekciji Prodaja.
PASS v0.9 korisničke Moje provizije ostaju dostupne kroz Home Moje aktivnosti i view-own list/detail tok.
PASS v0.7 Admin Provizije zadržavaju list/detail/bulk-pay/export/status workflow.
PASS v0.7 Provizije koriste postojeći Admin API i secure export bez paralelne logike.
PASS v0.7 release-critical Provizije ostaju vezane za kompletan canonical Admin OpenAPI surface.
PASS v1.0 Commission policy koristi automatskih 10 procenata bez plafona i SuperAdmin-gated ručni unos bez policy disclosure-a.
PASS v0.8 Shipment UI koristi centralni courier izbor, tracking URL i canonical courier_service_id.
PASS v0.8 Courier Directory Mobile API pokriva list/create/update bez delete workflow-a.
PASS v0.8 Courier Directory UI je SuperAdmin-only i uređuje HTTPS tracking, status, default i redosled.
PASS v0.8 Admin Hub izlaže centralni Courier Directory SuperAdministratoru.
PASS OpenAPI dokumentuje centralni Courier Directory list/create/update ugovor.
PASS v0.8 Admin User request deli Laravel permission, unique identitet i 12-char password contract.
PASS v0.8 centralni AdminUserService opoziva tokene, auditira izmene i štiti poslednjeg aktivnog SuperAdmina.
PASS v0.8 User Management API pokriva list/options/detail/create/update bez delete workflow-a.
PASS v0.8 Mobile User API pokriva kompletan Laravel User Manager bez hard delete-a.
PASS v0.8 shared User form pokriva identitet, ulogu, grupu, status i password management.
PASS v0.8 User Management UI ima permission-gated list/create/edit i self-password reauthentication.
PASS v0.8 User Management query keys i Admin Hub entry su centralizovani.
PASS OpenAPI dokumentuje kompletan Admin User list/options/detail/create/update ugovor.
PASS v0.8 Exchange Rate API koristi centralni ExchangeRateService i 50 zapisa istorije.
PASS v0.8 Mobile Exchange Rate API pokriva state, manual, automatic i refresh ugovor.
PASS v0.8 Exchange Rate ekran ima permission-gated manual/automatic/refresh/history UX.
PASS v0.8 Exchange Rate UI koristi canonical API client bez paralelnog fetch toka.
PASS v0.8 Exchange Rate query key i Admin Hub entry su centralizovani.
PASS OpenAPI dokumentuje kompletan EUR/RSD Admin contract.
PASS v0.9 Brand Manager koristi relativni centralizovani API ugovor sa type-scoped brand/line podacima.
PASS v0.9 Mobile Brand Manager je permission-gated i pokriva globalni filter/search/add/edit/type/line UX bez Product Variants.
PASS v0.9/v1.0 Admin Hub izlaže Šifarnike taxonomy administratorima i pretraga obuhvata Brendove.
PASS v0.9 Brand Manager koristi centralizovane TanStack query keys.
PASS v0.9 OpenAPI dokumentuje globalni Brand Manager read/create/update/options ugovor i taxonomy permission.
PASS v0.9 Home prikazuje dve SuperAdmin inventory valuation pločice ispod postojeća četiri KPI-ja i pre Finansijskog pulsa.
PASS v0.9 Home inventory KPI koristi postojeći centralizovani Admin Foundation valuation contract.
PASS v0.9 Product detalj prikazuje server proviziju, SuperAdmin Direct Sale i Uredi artikal kao poslednju admin akciju.
PASS v0.9 Catalog kartica prikazuje server obračunatu proviziju.
PASS v0.9 Product detail/catalog commission tok ostaje product-only bez Product Variants.
PASS v0.9 Direct Sale deferred tok ostavlja finansijski saldo otvoren i koristi postojeći Receivables plan.
PASS v0.9 deferred Direct Sale dozvoljava payment lifecycle, blokira ad-hoc refund i čuva canonical after-sales refund.
PASS v0.9 Direct Sale API validira odloženo plaćanje, 1–24 rate i konačni datum.
PASS v0.9 Mobile Direct Sale API ugovor sadrži deferred payment polja.
PASS v0.9 Direct Sale ekran prikazuje uslovni plan rata i konačni datum pune isplate.
PASS OpenAPI dokumentuje deferred Direct Sale payment metodu, rate i konačni datum.
PASS v0.9 Direct Sale deferred tok ne vraća Product Variants.
PASS v0.9 Bottom navigation ostaje Početna, Katalog, Porudžbine, Obaveštenja, Nalog.
PASS v0.9 Home prati Fokus danas > Brze akcije > Moje aktivnosti > Administracija hijerarhiju.
PASS v0.9 Moje aktivnosti centralizuju porudžbine, provizije, garancije i postprodaju.
PASS v0.9 Porudžbine prikazuju Moje i Dodeljene bez cross-feature prečica.
PASS v0.9 Admin Hub je permission-filtered, pretraživ i grupisan u šest poslovnih sekcija.
PASS v0.9 Nalog prati Profil > Bezbednost > Obaveštenja > Uređaji > Odjava redosled.
PASS v0.9 Navigation reorganizacija ne vraća Product Variants.
PASS v1.0 Batch50 V2 lokalna tema i valuta su per-user/per-device SecureStore preference bez server write-a.
PASS v1.0 Batch50 V2 Profil ima moderne single-choice Tema i Primarna valuta kontrole.
PASS v1.0 Batch50 V2 lokalna tema upravlja RN/Tamagui/StatusBar shell-om posle korisničke preference hidratacije.
PASS v1.0 Batch50 V2 bootstrap izlaže samo read-only presentation metadata postojećeg NBS authority-ja.
PASS v1.0 Batch50 V2 NBS Komercijalni prodajni authority ostaje jedini EUR/RSD source.
PASS v1.0 Batch50 V2 primarna valuta je display-only; canonical RSD payment input i payload ostaju nepromenjeni.
PASS v1.0 Batch50 V2 globalni loading koristi branded Reanimated pulse i skeleton.
PASS v1.0 Batch50 V2 bottom nav drži centralno izdvojenu Početnu i role-aware Admin/Obaveštenja četvrti slot.
PASS v1.0 Batch50 V2 header desno koristi notification bell+badge umesto profila, a Nalog ostaje u bottom nav-u.
PASS v1.0 Batch50 V2 čuva Katalog active context za admin/catalog edit, dok ostali admin ekrani aktiviraju Admin slot.
PASS v1.0 Šifarnici hub je permission-gated i vodi na svih pet canonical destinacija bez duplog Brand Managera.
PASS v1.0 Dictionary API koristi relativni centralizovani CRUD/reorder/purge/product-type ugovor.
PASS v1.0 Mobile šifarnici pokrivaju create/update/deactivate/reorder i bezbedni specification purge sa korelacijama.
PASS v1.0 Product Type detalj pokriva kompletan CMS field/completeness/name-template i reorder ugovor.
PASS v1.0 postojeći Global Brand Manager ostaje canonical CRUD ekran i dobija shared reorder bez duplog odredišta.
PASS v1.0 Brand Manager podržava do deset type-scoped linija i dinamički Mobile add/remove editor.
PASS v1.0 Admin Hub postavlja Šifarnike u Katalog i lager i pretraga nalazi ugnježdene opcije.
PASS v1.0 Dictionary TanStack query keys su centralizovani.
PASS OpenAPI dokumentuje kompletan ADMIN-CAT-10 dictionary route surface.
PASS v1.0 Šifarnici ne vraćaju aktivni Product Variants contract.
PASS v1.0 Admin Orders PDF/shipment binary putanje su relativne i ne dupliraju /api/v1 prefiks.
PASS v1.0 Admin Order detalj ugrađuje permission-gated Poslovni dokumenti workbench bez orphan ekrana.
PASS v1.0 Mobile document workbench pokriva predračun, račun, otpremnicu, istoriju revizija i kontrolisano storniranje.
PASS v1.0 Admin dokument PDF koristi authenticated Bearer download, PDF signature proveru i privatni cache/share flow.
PASS v1.0 Admin dokumenti ostaju product-only bez Product Variants contracta.
PASS v1.0 Admin Catalog API pokriva server-driven deletion readiness, purge i Total Product Purge.
PASS v1.0 Mobile Product detalj ima postojeći archive/restore plus kontrolisani purge i SuperAdmin Total Product Purge danger-zone workflow.
PASS v1.0 Admin Catalog deletion API reuse-uje postojeće Laravel ProductDeletionService i TotalProductPurgeService ZERO TRACE guardove.
PASS OpenAPI dokumentuje ADMIN-CAT-03 deletion readiness, purge i Total Product Purge ugovor.
PASS v1.0 Admin Catalog purge tok ostaje product-only bez Product Variants contracta.

Ukupno FAIL: 0
PASS v1.0 System Health Mobile API pokriva snapshot, backup i retention mutacije relativnim canonical putanjama.
PASS v1.0 System Health API reuse-uje postojeće SystemHealthService i BackupService business guardove bez paralelne logike.
PASS v1.0 System Health Mobile state izlaže bezbednu backup/security istoriju bez privatnih backup putanja.
PASS v1.0 System Health ekran pokriva Web health/backup workflow uz kontrolisani retention confirm i repeatable-action contract.
PASS v1.0 System Health API rute imaju system.health/backups.manage i odgovarajuće write/backup throttle guardove.
PASS OpenAPI dokumentuje kompletan SET-03 System Health GET/run/backup/prune i safe history ugovor.
PASS v1.0 System Health parity ne vraća Product Variants contract.
PASS v1.0 PORTAL-01 Mobile pokriva customer inbox/create/detail/reply kroz relativni canonical API.
PASS v1.0 PORTAL-ADMIN-01 Mobile pokriva customer create/invite/order-link/session-revoke i conversation workflow.
PASS v1.0 Customer Portal API rute čuvaju customer ownership i admin permission/throttle granice.
PASS v1.0 Customer Portal Web i Mobile write workflow dele isti CustomerPortalAdminService authority.
PASS v1.0 Customer Portal je organizovan u Moje aktivnosti i Admin/Korisnici uz module visibility.
PASS OpenAPI dokumentuje PORTAL-01 i PORTAL-ADMIN-01 route surface i popravlja raniji purchase-cost/module-settings line-break drift.
PASS v1.0 Customer Portal parity ne vraća Product Variants contract.
PASS v1.0 USER-02 Mobile API pokriva User Groups list/create/update/delete relativni canonical ugovor.
PASS v1.0 USER-02 Mobile ekran pokriva permission, category scope, status, sort i bezbedni delete workflow.
PASS v1.0 USER-02 Admin Hub drži Grupe pristupa u organizovanoj Korisnici sekciji.
PASS v1.0 USER-02 TanStack query keys su centralizovani.
PASS v1.0 USER-02 API rute dele system.manage_users granicu i puni CRUD surface.
PASS v1.0 USER-02 Web i Mobile API dele isti UserGroupAdminService i AdminUserGroupRequest authority.
PASS v1.0 USER-02 shared servis čuva permission/category sync i blokira brisanje grupe sa korisnicima.
PASS OpenAPI dokumentuje kompletan USER-02 User Groups CRUD ugovor.
PASS v1.0 USER-02 parity ne vraća Product Variants contract.
PASS v1.0 glavni EUR/RSD authority je zakljucan na NBS Komercijalni prodajni kurs.
PASS Web i Mobile jasno oznacavaju Komercijalni prodajni kao glavni kurs.
PASS v1.0 ADMIN-CAT-04 Mobile pokriva clone, name preview i regenerate-name kroz canonical ProductAdmin/ProductTemplate authority.
PASS v1.0 ADMIN-CAT-05 Mobile pokriva bulk selection, preview i execute kroz postojeći ProductBulkService.
PASS v1.0 ADMIN-CAT-11 Mobile pokriva Data Quality audit, safe repair, history i JSON export.
PASS v1.0 CATALOG_ADVANCED catalog.manage_products API route surface je kompletan.
PASS v1.0 ADMIN-CAT-11 API čuva catalog.audit granicu i shared DataQualityService authority.
PASS v1.0 ADMIN-CAT-04/05 API reuse-uje postojeće Laravel catalog authority servise bez paralelne poslovne logike.
PASS v1.0 CATALOG_ADVANCED opcije ostaju organizovane u Katalog i lager Admin grupi.
PASS v1.0 CATALOG_ADVANCED TanStack query keys su centralizovani.
PASS OpenAPI dokumentuje kompletan CATALOG_ADVANCED route surface.
PASS v1.0 CATALOG_ADVANCED parity ne vraća Product Variants contract.
PASS v1.0 ADMIN-ORDER-02 Mobile API pokriva archived list, archive, restore i purge ugovor.
PASS v1.0 ADMIN-ORDER-02 Mobile UI pokriva arhivu, restore i SuperAdmin purge sa kontrolisanom potvrdom.
PASS v1.0 ADMIN-ORDER-02 ostaje organizovan u postojećem Prodaja/Porudžbine toku.
PASS v1.0 ADMIN-ORDER-02 API route surface je kompletan.
PASS v1.0 ADMIN-ORDER-02 reuse-uje canonical OrderArchiveService i čuva SuperAdmin-only purge.
PASS v1.0 REPORT-02 Mobile pokriva orders PDF/CSV, payments CSV i inventory CSV kroz secure Bearer download.
PASS v1.0 REPORT-02 API route surface je kompletan.
PASS v1.0 REPORT-02 Web i Mobile API dele isti OrderReportService export authority.
PASS v1.0 ORDER_REPORT_OPS TanStack query keys su centralizovani.
PASS OpenAPI dokumentuje kompletan ORDER_REPORT_OPS route surface.
PASS v1.0 ORDER_REPORT_OPS parity ne vraća Product Variants contract.
PASS v1.0 SYSTEM_SETTINGS file picker koristi Expo SDK57 literal overload za single/multiple izbor.
PASS v1.0 SET-02 Mobile pokriva automation settings, manual run i resolve alert kroz canonical automation authority.
PASS v1.0 SET-04 Mobile pokriva Turnstile bez izlaganja secret vrednosti.
PASS v1.0 SET-05 Mobile pokriva brending, footer i SuperAdmin login background/slideshow asset workflow.
PASS v1.0 SET-08 Mobile pokriva order e-mail settings, dispatch i retry workflow.
PASS v1.0 SET-09 Mobile pokriva poslovne dokumente i kontrolisani PDF logo lifecycle.
PASS v1.0 SET-10 Mobile pokriva Bank Accounts CRUD uz canonical server MOD97 validaciju.
PASS v1.0 SYSTEM_SETTINGS opcije su organizovane kroz jedan Sistem hub bez zagušenja glavne administracije.
PASS v1.0 SYSTEM_SETTINGS TanStack query keys su centralizovani.
PASS v1.0 SYSTEM_SETTINGS API route surface ima 20 kontrolisanih operacija sa permission/throttle granicama.
PASS v1.0 SYSTEM_SETTINGS API reuse-uje postojeće Web/service authority-je umesto paralelne poslovne logike.
PASS OpenAPI dokumentuje kompletan SYSTEM_SETTINGS route surface.
PASS v1.0 SYSTEM_SETTINGS parity ne vraća Product Variants contract.
PASS v1.0 CAT-02 Mobile API koristi relativni centralizovani global-search ugovor i tipizovane Mobile targete.
PASS v1.0 CAT-02 Mobile ekran pokriva debounce, grouped rezultate i navigaciju kroz server-driven Mobile target.
PASS v1.0 CAT-02 Global Search je organizovan kao jedna jasna Admin quick-action destinacija bez zagušenja poslovnih sekcija.
PASS v1.0 CAT-02 user rezultat otvara postojeći User Manager sa primenjenim q filterom.
PASS v1.0 CAT-02 TanStack query key je centralizovan.
PASS v1.0 CAT-02 API ruta je auth/active nasledjena i čuva postojeći Web search throttle.
PASS v1.0 CAT-02 Mobile API reuse-uje postojeći GlobalCommandSearchService authority i samo adaptira Web URL u Mobile target.
PASS OpenAPI dokumentuje kompletan CAT-02 permission-aware Global Search ugovor.
PASS v1.0 CAT-02 Global Search parity ne vraća Product Variants contract.
npm notice
npm notice New major version of npm available! 10.9.8 -> 12.0.2
npm notice Changelog: https://github.com/npm/cli/releases/tag/v12.0.2
npm notice To update run: npm install -g npm@12.0.2
npm notice
MOBILE_BASELINE_GATES=PASS_VALIDATE_AND_TSC_NO_EMIT

============================================================
1. RUNTIME + STORAGE BASELINE
============================================================
NODE_VERSION=v22.23.2
NPM_VERSION=10.9.8
PHP_VERSION=8.4.24
FILESYSTEM_FREE_KB=793697484
PROJECT_SIZE_KB=1201976
MOBILE_TOTAL_SIZE_KB=600948
MOBILE_NODE_MODULES_SIZE_KB=578428
MOBILE_SRC_SIZE_KB=2056
MOBILE_ASSETS_SIZE_KB=376
CMS_TOTAL_SIZE_KB=552304
CMS_VENDOR_SIZE_KB=46424
CLEAN_STABLE_SIZE_KB=562360
MOBILE_TS_TSX_FILE_COUNT=178
MOBILE_TS_TSX_LOC=37480
CMS_APP_PHP_FILE_COUNT=434
CMS_APP_PHP_LOC=58863

============================================================
1A. TOP MOBILE SOURCE FILES BY LOC
============================================================
00001295 000000044261 src/app/(app)/admin/reports/index.tsx
00001164 000000026333 src/app/(app)/(tabs)/account.tsx
00000959 000000023419 src/app/(app)/admin/warranties/[id].tsx
00000914 000000024386 src/types/api.ts
00000827 000000033954 src/app/(app)/admin/catalog/create.tsx
00000660 000000032952 src/app/(app)/admin/catalog/[id].tsx
00000653 000000019753 src/app/(app)/after-sales/create/[orderId].tsx
00000641 000000024734 src/app/(app)/order/[id].tsx
00000623 000000022695 src/app/(app)/after-sales/[id].tsx
00000621 000000023942 src/app/(app)/(tabs)/home.tsx
00000561 000000031100 src/features/admin/orders-admin-actions.tsx
00000560 000000016075 src/app/(app)/admin/warranties/rules.tsx
00000527 000000029315 src/app/(app)/admin/after-sales/[id].tsx
00000527 000000010160 src/components/ui/app-feedback.tsx
00000492 000000026069 src/app/(app)/admin/field-operations/[id].tsx
00000489 000000016774 src/features/catalog/product-image-manager.tsx
00000476 000000026302 src/app/(app)/admin/inventory/index.tsx
00000449 000000014396 src/app/(app)/commissions/index.tsx
00000441 000000022682 src/app/(app)/admin/catalog/dictionaries/[resource].tsx
00000435 000000010623 src/app/(app)/admin/warranties/index.tsx
00000422 000000017459 src/app/(app)/admin/catalog/brands/index.tsx
00000403 000000020541 src/app/(app)/admin/receivables/[id].tsx
00000393 000000014252 src/app/(app)/admin/orders/index.tsx
00000392 000000017815 src/app/(app)/admin/user-groups/index.tsx
00000384 000000012455 src/app/(app)/admin/audit/index.tsx
00000372 000000017526 src/app/(app)/admin/orders/[id].tsx
00000366 000000019703 src/app/(app)/admin/receivables/index.tsx
00000365 000000016035 src/app/(app)/admin/catalog/[id]/direct-sale.tsx
00000355 000000013161 src/features/admin/orders-admin-api.ts
00000355 000000008903 src/features/admin/reports-admin-api.ts
00000346 000000010137 src/features/admin/after-sales-admin-api.ts
00000339 000000010311 src/features/admin/catalog-admin-api.ts
00000337 000000012211 src/features/admin/order-documents-admin.tsx
00000336 000000014139 src/app/(app)/admin/system-health/index.tsx
00000330 000000016859 src/app/(app)/admin/index.tsx
00000328 000000008278 src/features/orders/order-post-create-files.ts
00000325 000000017605 src/app/(app)/admin/catalog/dictionaries/product-types/[id].tsx
00000320 000000011910 src/lib/api/endpoints.ts
00000313 000000007183 src/features/admin/warranties-admin-api.ts
00000308 000000010056 src/app/(app)/warranties/[id].tsx

============================================================
1B. TOP MOBILE ASSETS BY SIZE
============================================================
000000062851 assets/splash-icon.png
000000062851 assets/icon.png
000000062851 assets/favicon.png
000000062851 assets/brand/ald1n-v2-logo.png
000000062851 assets/adaptive-icon.png
000000029487 assets/icon-ios.png
000000001608 assets/brand/ald1n-v2-logo.svg

============================================================
1C. DIRECT DEPENDENCY INSTALLED SIZE
============================================================
000000036240 react-native
000000016204 expo
000000013736 expo-router
000000011716 react-native-reanimated
000000009736 react-native-screens
000000008396 expo-file-system
000000007340 react-native-gesture-handler
000000007288 react-dom
000000006832 react-native-web
000000006524 zod
000000005460 tamagui
000000003784 expo-notifications
000000003068 expo-updates
000000002748 @hookform/resolvers
000000002688 react-native-worklets
000000002464 expo-font
000000002204 react-hook-form
000000001856 @tanstack/react-query
000000001780 @tamagui/config
000000001564 react-native-nitro-modules
000000001184 react-native-nitro-google-signin
000000000960 react-native-safe-area-context
000000000888 expo-sharing
000000000772 expo-splash-screen
000000000768 expo-crypto
000000000748 expo-symbols
000000000640 expo-clipboard
000000000572 expo-linking
000000000564 expo-secure-store
000000000508 @tamagui/animations-reanimated
000000000480 expo-localization
000000000452 expo-system-ui
000000000428 expo-device
000000000384 expo-application
000000000352 expo-constants
000000000328 expo-status-bar
000000000260 react
000000000244 expo-dev-client
DIRECT_DEPENDENCY_COUNT=38
INSTALLED_PACKAGE_JSON_COUNT=775

============================================================
2. REACT / UI PERFORMANCE SIGNALS
============================================================
USE_QUERY_CALLS=0
USE_INFINITE_QUERY_CALLS=0
USE_MUTATION_CALLS=0
INVALIDATE_QUERY_CALLS=77
STALE_TIME_DECLARATIONS=14
GC_TIME_DECLARATIONS=0
REFETCH_POLICY_DECLARATIONS=1
FLATLIST_REFERENCES=16
SECTIONLIST_REFERENCES=0
SCROLLVIEW_REFERENCES=24
FLASHLIST_REFERENCES=0
REACT_MEMO_REFERENCES=0
USE_MEMO_REFERENCES=0
USE_CALLBACK_REFERENCES=0
RN_IMAGE_REFERENCES=10
EXPO_IMAGE_REFERENCES=0
SECURESTORE_REFERENCES=13
SET_INTERVAL_REFERENCES=0
SET_TIMEOUT_REFERENCES=0

============================================================
2A. QUERY CALL SITES
============================================================
src/features/admin/order-archive-admin.tsx:33:  const mutation = useMutation({
src/features/admin/catalog-advanced-product-actions.tsx:30:  const regenerateMutation = useMutation({
src/features/admin/orders-admin-actions.tsx:211:  const supplierQuery = useQuery({
src/features/admin/orders-admin-actions.tsx:221:  const mutation = useMutation({
src/features/admin/product-deletion-admin.tsx:45:  const deletionQuery = useQuery({
src/features/admin/product-deletion-admin.tsx:50:  const purgeMutation = useMutation({
src/features/admin/product-deletion-admin.tsx:68:  const totalPurgeMutation = useMutation({
src/features/admin/order-documents-admin.tsx:89:  const confirmationMutation = useMutation({
src/features/admin/order-documents-admin.tsx:124:  const issueMutation = useMutation({
src/features/admin/order-documents-admin.tsx:139:  const cancelMutation = useMutation({
src/features/catalog/product-image-manager.tsx:226:  const query = useQuery({
src/features/catalog/product-image-manager.tsx:237:  const mutation = useMutation({
src/features/catalog/product-image-manager.tsx:262:  const uploadMutation = useMutation({
src/app/(app)/(tabs)/notifications.tsx:31:  const query = useQuery({ queryKey: ['notifications'], queryFn: () => api.notifications.list(), enabled: allowed });
src/app/(app)/(tabs)/notifications.tsx:32:  const read = useMutation({
src/app/(app)/(tabs)/notifications.tsx:68:  const readAll = useMutation({
src/app/(app)/(tabs)/catalog.tsx:27:  const filters = useQuery({
src/app/(app)/(tabs)/catalog.tsx:33:  const query = useQuery({
src/app/(app)/(tabs)/home.tsx:44:  const foundationQuery = useQuery({
src/app/(app)/(tabs)/home.tsx:50:  const reportQuery = useQuery({
src/app/(app)/(tabs)/orders.tsx:24:  const query = useQuery({ queryKey: ['orders'], queryFn: () => api.orders.list(), enabled: allowed });
src/app/(app)/commissions/[id].tsx:35:  const query = useQuery({
src/app/(app)/commissions/index.tsx:112:  const query = useQuery({
src/app/(app)/order/[id].tsx:82:  const query = useQuery({
src/app/(app)/order/[id].tsx:88:  const postCreateQuery = useQuery({
src/app/(app)/order/[id].tsx:94:  const cancel = useMutation({
src/app/(app)/order/[id].tsx:103:  const proofMutation = useMutation({
src/app/(app)/notification-settings.tsx:47:  const devices = useQuery({ queryKey: ['devices'], queryFn: api.devices.list, enabled: allowed && hasFeature('mobile_devices') });
src/app/(app)/notification-settings.tsx:63:  const preferenceMutation = useMutation({
src/app/(app)/checkout.tsx:28:  const options = useQuery({ queryKey: ['order-options'], queryFn: api.orders.options, enabled: allowed && items.length > 0, staleTime: 5 * 60_000 });
src/app/(app)/checkout.tsx:64:  const mutation = useMutation({
src/app/(app)/product/[slug].tsx:35:  const query = useQuery({ queryKey: ['product', slug], queryFn: () => api.catalog.product(slug), enabled: allowed && Boolean(slug) });
src/app/(app)/admin/commissions/[id].tsx:60:  const query = useQuery({
src/app/(app)/admin/commissions/[id].tsx:66:  const mutation = useMutation({
src/app/(app)/admin/commissions/index.tsx:86:  const query = useQuery({
src/app/(app)/admin/commissions/index.tsx:92:  const bulkMutation = useMutation({
src/app/(app)/admin/receivables/[id].tsx:119:  const query = useQuery({
src/app/(app)/admin/receivables/[id].tsx:124:  const mutation = useMutation({ mutationFn: (run: () => Promise<unknown>) => run() });
src/app/(app)/admin/receivables/index.tsx:101:  const query = useQuery({
src/app/(app)/admin/receivables/index.tsx:106:  const mutation = useMutation({ mutationFn: (run: () => Promise<unknown>) => run() });
src/app/(app)/admin/users/[id].tsx:52:  const userQuery = useQuery({
src/app/(app)/admin/users/[id].tsx:58:  const optionsQuery = useQuery({
src/app/(app)/admin/users/[id].tsx:65:  const mutation = useMutation({
src/app/(app)/admin/users/create.tsx:42:  const optionsQuery = useQuery({
src/app/(app)/admin/users/create.tsx:49:  const mutation = useMutation({
src/app/(app)/admin/users/index.tsx:67:  const query = useQuery({
src/app/(app)/admin/settings/modules/index.tsx:28:  const query = useQuery({
src/app/(app)/admin/settings/modules/index.tsx:44:  const mutation = useMutation({
src/app/(app)/admin/settings/turnstile.tsx:26:  const query = useQuery({ queryKey: adminQueryKeys.systemSettingsTurnstile(), queryFn: apiAdminSystemSettings.turnstile.state, enabled: allowed });
src/app/(app)/admin/settings/turnstile.tsx:32:  const mutation = useMutation({ mutationFn: () => apiAdminSystemSettings.turnstile.update({ turnstile_enabled: enabled, turnstile_site_key: siteKey, turnstile_secret_key: secretKey || undefined, turnstile_expected_hostname: hostname }), onSuccess: (response) => { client.setQueryData(adminQueryKeys.systemSettingsTurnstile(), response); setSecretKey(''); feedback.notify({ tone: 'success', title: 'Turnstile je sačuvan' }); }, onError: (error) => feedback.notify({ tone: 'danger', title: 'Turnstile nije sačuvan', message: error instanceof Error ? error.message : 'Greška.' }) });
src/app/(app)/admin/settings/documents.tsx:21:  const query = useQuery({ queryKey: adminQueryKeys.systemSettingsDocuments(), queryFn: apiAdminSystemSettings.documents.state, enabled: allowed });
src/app/(app)/admin/settings/documents.tsx:23:  const save = useMutation({ mutationFn: async () => { const form = new FormData(); ['documents_company_name','documents_company_address','documents_company_city','documents_company_tax_id','documents_company_registration_number','documents_company_phone','documents_company_email','documents_company_website','documents_vat_rate','documents_payment_due_days','documents_default_note','documents_footer_note'].forEach((key)=>form.append(key,values[key]??'')); form.append('documents_vat_enabled',values.documents_vat_enabled==='1'?'1':'0'); if(logo) appendAdminSystemFile(form,'documents_logo',logo); return apiAdminSystemSettings.documents.update(form); }, onSuccess:(response)=>{client.setQueryData(adminQueryKeys.systemSettingsDocuments(),response);feedback.notify({tone:'success',title:'Dokument podešavanja su sačuvana'});},onError:(error)=>feedback.notify({tone:'danger',title:'Podešavanja nisu sačuvana',message:error instanceof Error?error.message:'Greška.'}) });
src/app/(app)/admin/settings/documents.tsx:24:  const remove = useMutation({ mutationFn: apiAdminSystemSettings.documents.removeLogo, onSuccess:(response)=>client.setQueryData(adminQueryKeys.systemSettingsDocuments(),response), onError:(error)=>feedback.notify({tone:'danger',title:'Logo nije uklonjen',message:error instanceof Error?error.message:'Greška.'}) });
src/app/(app)/admin/settings/automation.tsx:35:  const query = useQuery({ queryKey: adminQueryKeys.systemSettingsAutomation(), queryFn: apiAdminSystemSettings.automation.state, enabled: allowed });
src/app/(app)/admin/settings/automation.tsx:41:  const mutation = useMutation({
src/app/(app)/admin/settings/order-emails.tsx:27:  const query = useQuery({ queryKey: adminQueryKeys.systemSettingsOrderEmails(), queryFn: apiAdminSystemSettings.orderEmails.state, enabled: allowed });
src/app/(app)/admin/settings/order-emails.tsx:29:  const save = useMutation({ mutationFn: () => apiAdminSystemSettings.orderEmails.update({ ...values, ...Object.fromEntries(FLAGS.map(([key]) => [key, values[key] === '1'])) }), onSuccess: (response) => { client.setQueryData(adminQueryKeys.systemSettingsOrderEmails(), response); feedback.notify({ tone: 'success', title: 'E-mail podešavanja su sačuvana' }); }, onError: (error) => feedback.notify({ tone: 'danger', title: 'Podešavanja nisu sačuvana', message: error instanceof Error ? error.message : 'Greška.' }) });
src/app/(app)/admin/settings/order-emails.tsx:30:  const action = useMutation({ mutationFn: (kind: 'dispatch'|'retry') => kind === 'dispatch' ? apiAdminSystemSettings.orderEmails.dispatch() : apiAdminSystemSettings.orderEmails.retry(), onSuccess: (response) => { client.setQueryData(adminQueryKeys.systemSettingsOrderEmails(), response); feedback.notify({ tone: 'success', title: response.message ?? 'Outbox je ažuriran' }); }, onError: (error) => feedback.notify({ tone: 'danger', title: 'Outbox akcija nije uspela', message: error instanceof Error ? error.message : 'Greška.' }) });
src/app/(app)/admin/settings/bank-accounts.tsx:23:  const query=useQuery({queryKey:adminQueryKeys.systemSettingsBankAccounts(),queryFn:apiAdminSystemSettings.bankAccounts.state,enabled:allowed});
src/app/(app)/admin/settings/bank-accounts.tsx:25:  const save=useMutation({mutationFn:()=>editing===null?apiAdminSystemSettings.bankAccounts.create(form):apiAdminSystemSettings.bankAccounts.update(editing,form),onSuccess:(response)=>{client.setQueryData(adminQueryKeys.systemSettingsBankAccounts(),response);setEditing(null);setForm(EMPTY);feedback.notify({tone:'success',title:'Žiro račun je sačuvan'});},onError:(error)=>feedback.notify({tone:'danger',title:'Račun nije sačuvan',message:error instanceof Error?error.message:'Greška.'})});
src/app/(app)/admin/settings/bank-accounts.tsx:26:  const remove=useMutation({mutationFn:apiAdminSystemSettings.bankAccounts.remove,onSuccess:(response)=>client.setQueryData(adminQueryKeys.systemSettingsBankAccounts(),response),onError:(error)=>feedback.notify({tone:'danger',title:'Račun nije obrisan',message:error instanceof Error?error.message:'Greška.'})});
src/app/(app)/admin/settings/appearance.tsx:29:  const query = useQuery({ queryKey: adminQueryKeys.systemSettingsAppearance(), queryFn: apiAdminSystemSettings.appearance.state, enabled: allowed });
src/app/(app)/admin/settings/appearance.tsx:36:  const mutation = useMutation({
src/app/(app)/admin/settings/appearance.tsx:65:  const removeMutation = useMutation({ mutationFn: apiAdminSystemSettings.appearance.removeAsset, onSuccess: (response) => client.setQueryData(adminQueryKeys.systemSettingsAppearance(), response), onError: (error) => feedback.notify({ tone: 'danger', title: 'Fajl nije uklonjen', message: error instanceof Error ? error.message : 'Greška.' }) });
src/app/(app)/admin/exchange-rate/index.tsx:64:  const query = useQuery({
src/app/(app)/admin/exchange-rate/index.tsx:71:  const mutation = useMutation({
src/app/(app)/admin/customer-portal/conversations/[id].tsx:33:  const query = useQuery({
src/app/(app)/admin/customer-portal/conversations/[id].tsx:39:  const reply = useMutation({
src/app/(app)/admin/customer-portal/conversations/[id].tsx:51:  const update = useMutation({
src/app/(app)/admin/customer-portal/[userId].tsx:34:  const query = useQuery({
src/app/(app)/admin/customer-portal/[userId].tsx:44:  const invite = useMutation({
src/app/(app)/admin/customer-portal/[userId].tsx:51:  const revoke = useMutation({
src/app/(app)/admin/customer-portal/[userId].tsx:62:  const link = useMutation({
src/app/(app)/admin/customer-portal/index.tsx:36:  const query = useQuery({
src/app/(app)/admin/customer-portal/index.tsx:41:  const create = useMutation({
src/app/(app)/admin/orders/[id].tsx:90:  const query = useQuery({
src/app/(app)/admin/orders/archived.tsx:52:  const query = useQuery({
src/app/(app)/admin/orders/archived.tsx:62:  const restoreMutation = useMutation({
src/app/(app)/admin/orders/archived.tsx:79:  const purgeMutation = useMutation({
src/app/(app)/admin/orders/index.tsx:89:  const query = useQuery({
src/app/(app)/admin/user-groups/index.tsx:117:  const query = useQuery({
src/app/(app)/admin/user-groups/index.tsx:123:  const saveMutation = useMutation({
src/app/(app)/admin/user-groups/index.tsx:144:  const deleteMutation = useMutation({
src/app/(app)/admin/reports/index.tsx:161:  const query = useQuery({
src/app/(app)/admin/reports/index.tsx:483:  const query = useQuery({
src/app/(app)/admin/reports/index.tsx:634:  const saveMutation = useMutation({
src/app/(app)/admin/reports/index.tsx:660:  const toggleMutation = useMutation({
src/app/(app)/admin/reports/index.tsx:677:  const runMutation = useMutation({
src/app/(app)/admin/reports/index.tsx:695:  const deleteMutation = useMutation({
src/app/(app)/admin/reports/index.tsx:714:  const retryMutation = useMutation({
src/app/(app)/admin/service-parts/index.tsx:98:  const query = useQuery({
src/app/(app)/admin/service-parts/index.tsx:103:  const mutation = useMutation({ mutationFn: (run: () => Promise<unknown>) => run() });
src/app/(app)/admin/service-parts/purchases/[id].tsx:38:  const query = useQuery({ queryKey: adminQueryKeys.servicePartPurchase(purchaseId), queryFn: () => apiAdminServiceParts.purchaseDetail(purchaseId), enabled: allowed && Number.isInteger(purchaseId) && purchaseId > 0 });
src/app/(app)/admin/service-parts/purchases/[id].tsx:39:  const mutation = useMutation({ mutationFn: (run: () => Promise<unknown>) => run() });
src/app/(app)/admin/service-parts/purchases/index.tsx:63:  const query = useQuery({ queryKey: adminQueryKeys.servicePartPurchases(applied), queryFn: () => apiAdminServiceParts.purchasesList(applied), enabled: allowed });
src/app/(app)/admin/service-parts/purchases/index.tsx:64:  const mutation = useMutation({ mutationFn: (run: () => Promise<unknown>) => run() });
src/app/(app)/admin/service-parts/suppliers.tsx:48:  const query = useQuery({ queryKey: adminQueryKeys.servicePartSuppliers(applied), queryFn: () => apiAdminServiceParts.suppliersList(applied), enabled: allowed });
src/app/(app)/admin/service-parts/suppliers.tsx:49:  const mutation = useMutation({ mutationFn: (run: () => Promise<unknown>) => run() });
src/app/(app)/admin/audit/[id].tsx:47:  const query = useQuery({
src/app/(app)/admin/audit/index.tsx:70:  const query = useQuery({
src/app/(app)/admin/inventory/index.tsx:131:  const inventoryQuery = useQuery({
src/app/(app)/admin/inventory/index.tsx:137:  const lookupQuery = useQuery({
src/app/(app)/admin/inventory/index.tsx:143:  const movementsQuery = useQuery({
src/app/(app)/admin/inventory/index.tsx:149:  const mutation = useMutation({ mutationFn: (run: () => Promise<unknown>) => run() });
src/app/(app)/admin/warranties/[id].tsx:135:  const query = useQuery({
src/app/(app)/admin/warranties/[id].tsx:174:  const updateMutation = useMutation({
src/app/(app)/admin/warranties/[id].tsx:197:  const voidMutation = useMutation({
src/app/(app)/admin/warranties/[id].tsx:226:  const maintenanceMutation = useMutation({
src/app/(app)/admin/warranties/rules.tsx:116:  const query = useQuery({
src/app/(app)/admin/warranties/rules.tsx:217:  const saveMutation = useMutation({
src/app/(app)/admin/warranties/rules.tsx:248:  const backfillMutation = useMutation({
src/app/(app)/admin/warranties/index.tsx:89:  const query = useQuery({
src/app/(app)/admin/field-operations/[id].tsx:149:  const query = useQuery({
src/app/(app)/admin/field-operations/[id].tsx:154:  const mutation = useMutation({ mutationFn: (run: () => Promise<unknown>) => run() });
src/app/(app)/admin/field-operations/index.tsx:85:  const query = useQuery({
src/app/(app)/admin/system-health/index.tsx:91:  const query = useQuery({
src/app/(app)/admin/system-health/index.tsx:97:  const runMutation = useMutation({
src/app/(app)/admin/system-health/index.tsx:110:  const backupMutation = useMutation({
src/app/(app)/admin/system-health/index.tsx:127:  const pruneMutation = useMutation({
src/app/(app)/admin/after-sales/[id].tsx:115:  const query = useQuery({
src/app/(app)/admin/after-sales/[id].tsx:121:  const mutation = useMutation({ mutationFn: (run: () => Promise<unknown>) => run() });
src/app/(app)/admin/after-sales/index.tsx:95:  const query = useQuery({
src/app/(app)/admin/index.tsx:34:  const query = useQuery({
src/app/(app)/admin/search.tsx:45:  const query = useQuery({
src/app/(app)/admin/catalog/[id]/clone.tsx:50:  const detailQuery = useQuery({
src/app/(app)/admin/catalog/[id]/clone.tsx:69:  const previewMutation = useMutation({
src/app/(app)/admin/catalog/[id]/clone.tsx:86:  const cloneMutation = useMutation({
src/app/(app)/admin/catalog/[id]/direct-sale.tsx:97:  const optionsQuery = useQuery({
src/app/(app)/admin/catalog/[id]/direct-sale.tsx:113:  const saleMutation = useMutation({
src/app/(app)/admin/catalog/dictionaries/product-types/[id].tsx:59:  const query = useQuery({
src/app/(app)/admin/catalog/dictionaries/product-types/[id].tsx:104:  const saveMutation = useMutation({
src/app/(app)/admin/catalog/dictionaries/product-types/[id].tsx:114:  const reorderMutation = useMutation({
src/app/(app)/admin/catalog/dictionaries/[resource].tsx:85:  const listQuery = useQuery({
src/app/(app)/admin/catalog/dictionaries/[resource].tsx:96:  const saveMutation = useMutation({
src/app/(app)/admin/catalog/dictionaries/[resource].tsx:109:  const deactivateMutation = useMutation({
src/app/(app)/admin/catalog/dictionaries/[resource].tsx:122:  const reorderMutation = useMutation({
src/app/(app)/admin/catalog/dictionaries/[resource].tsx:134:  const purgeMutation = useMutation({
src/app/(app)/admin/catalog/[id].tsx:135:  const optionsQuery = useQuery({
src/app/(app)/admin/catalog/[id].tsx:140:  const detailQuery = useQuery({
src/app/(app)/admin/catalog/[id].tsx:204:  const saveMutation = useMutation({
src/app/(app)/admin/catalog/[id].tsx:228:  const archiveMutation = useMutation({
src/app/(app)/admin/catalog/[id].tsx:238:  const restoreMutation = useMutation({
src/app/(app)/admin/catalog/create.tsx:133:  const optionsQuery = useQuery({
src/app/(app)/admin/catalog/create.tsx:139:  const mutation = useMutation({
src/app/(app)/admin/catalog/data-quality/index.tsx:51:  const query = useQuery({
src/app/(app)/admin/catalog/data-quality/index.tsx:56:  const repairMutation = useMutation({
src/app/(app)/admin/catalog/data-quality/index.tsx:68:  const exportMutation = useMutation({
src/app/(app)/admin/catalog/bulk/index.tsx:71:  const productsQuery = useQuery({
src/app/(app)/admin/catalog/bulk/index.tsx:76:  const optionsQuery = useQuery({
src/app/(app)/admin/catalog/bulk/index.tsx:101:  const previewMutation = useMutation({
src/app/(app)/admin/catalog/bulk/index.tsx:106:  const executeMutation = useMutation({
src/app/(app)/admin/catalog/purchase-costs.tsx:32:  const query = useQuery({
src/app/(app)/admin/catalog/purchase-costs.tsx:38:  const mutation = useMutation({
src/app/(app)/admin/catalog/index.tsx:83:  const query = useQuery({
src/app/(app)/admin/catalog/brands/index.tsx:52:  const listQuery = useQuery({
src/app/(app)/admin/catalog/brands/index.tsx:57:  const optionsQuery = useQuery({
src/app/(app)/admin/catalog/brands/index.tsx:63:  const mutation = useMutation({
src/app/(app)/admin/catalog/brands/index.tsx:81:  const reorderMutation = useMutation({
src/app/(app)/admin/couriers/index.tsx:34:  const query = useQuery({ queryKey: ['admin', 'couriers'], queryFn: apiAdminCouriers.list, enabled: allowed });
src/app/(app)/admin/couriers/index.tsx:35:  const mutation = useMutation({
src/app/(app)/warranties/[id].tsx:49:  const query = useQuery({
src/app/(app)/warranties/index.tsx:85:  const query = useQuery({
src/app/(app)/portal/messages/[id].tsx:26:  const query = useQuery({ queryKey, queryFn: () => apiPortal.detail(id), enabled: allowed });
src/app/(app)/portal/messages/[id].tsx:27:  const reply = useMutation({
src/app/(app)/portal/messages/index.tsx:31:  const query = useQuery({ queryKey: portalKey, queryFn: apiPortal.list, enabled: allowed });
src/app/(app)/portal/messages/index.tsx:32:  const create = useMutation({
src/app/(app)/devices.tsx:29:  const query = useQuery({ queryKey: ['devices'], queryFn: api.devices.list, enabled: allowed });
src/app/(app)/devices.tsx:30:  const revoke = useMutation({ mutationFn: api.devices.revoke, onSuccess: async (_, id) => { const current = query.data?.find((item) => item.id === id)?.is_current; if (current) await signOut(); else await client.invalidateQueries({ queryKey: ['devices'] }); } });
src/app/(app)/assigned-orders/[id].tsx:22:  const query = useQuery({
src/app/(app)/assigned-orders/index.tsx:21:  const query = useInfiniteQuery({
src/app/(app)/after-sales/[id].tsx:73:  const query = useQuery({
src/app/(app)/after-sales/[id].tsx:78:  const messageMutation = useMutation({
src/app/(app)/after-sales/create/[orderId].tsx:68:  const options = useQuery({
src/app/(app)/after-sales/create/[orderId].tsx:109:  const mutation = useMutation({
src/app/(app)/after-sales/index.tsx:67:  const query = useQuery({
src/app/(app)/sessions.tsx:25:  const query = useQuery({ queryKey: ['account', 'sessions'], queryFn: api.account.sessions });
src/app/(app)/sessions.tsx:27:  const revoke = useMutation({
src/app/(app)/sessions.tsx:38:  const revokeOthers = useMutation({

============================================================
2B. LIST / SCROLL CONTAINER CALL SITES
============================================================
src/components/layout/screen.tsx:3:  ScrollView,
src/components/layout/screen.tsx:59:      <ScrollView
src/components/layout/screen.tsx:69:      </ScrollView>
src/components/ui/select-sheet.tsx:2:import { Modal, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
src/components/ui/select-sheet.tsx:76:            <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.options}>
src/components/ui/select-sheet.tsx:104:            </ScrollView>
src/components/ui/data-list.tsx:4:  FlatList,
src/components/ui/data-list.tsx:43:    <FlatList<T>
src/app/(auth)/reset-password.tsx:5:import { KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Text, View } from 'react-native';
src/app/(auth)/reset-password.tsx:48:    <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
src/app/(auth)/reset-password.tsx:61:    </ScrollView>
src/app/(auth)/login.tsx:6:import { Keyboard, KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Text, View } from 'react-native';
src/app/(auth)/login.tsx:27:  const scrollRef = useRef<ScrollView> (null);
src/app/(auth)/login.tsx:84:      <ScrollView
src/app/(auth)/login.tsx:132:      </ScrollView>
src/app/(auth)/activate-account.tsx:5:import { KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Text, View } from 'react-native';
src/app/(auth)/activate-account.tsx:57:    <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
src/app/(auth)/activate-account.tsx:71:    </ScrollView>
src/app/(auth)/forgot-password.tsx:5:import { KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Text, View } from 'react-native';
src/app/(auth)/forgot-password.tsx:37:    <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
src/app/(auth)/forgot-password.tsx:48:    </ScrollView>
src/app/(app)/(tabs)/notifications.tsx:4:import { FlatList, Pressable, RefreshControl, StyleSheet, Text, View } from 'react-native';
src/app/(app)/(tabs)/notifications.tsx:149:      <FlatList
src/app/(app)/(tabs)/catalog.tsx:4:import { FlatList, RefreshControl, StyleSheet, Text, TextInput, View } from 'react-native';
src/app/(app)/(tabs)/catalog.tsx:46:      <FlatList
src/app/(app)/(tabs)/orders.tsx:4:import { FlatList, RefreshControl, StyleSheet, Text, View } from 'react-native';
src/app/(app)/(tabs)/orders.tsx:31:      <FlatList
src/app/(app)/commissions/index.tsx:5:  FlatList,
src/app/(app)/commissions/index.tsx:8:  ScrollView,
src/app/(app)/commissions/index.tsx:163:      <FlatList
src/app/(app)/commissions/index.tsx:216:              <ScrollView
src/app/(app)/commissions/index.tsx:242:              </ScrollView>
src/app/(app)/warranties/index.tsx:4:import { FlatList, Pressable, RefreshControl, StyleSheet, Text, View } from 'react-native';
src/app/(app)/warranties/index.tsx:97:      <FlatList
src/app/(app)/assigned-orders/index.tsx:4:import { FlatList, Pressable, RefreshControl, StyleSheet, Text, View } from 'react-native';
src/app/(app)/assigned-orders/index.tsx:41:      <FlatList
src/app/(app)/after-sales/index.tsx:4:import { FlatList, Pressable, RefreshControl, StyleSheet, Text, View } from 'react-native';
src/app/(app)/after-sales/index.tsx:79:      <FlatList

============================================================
2C. LARGE MOBILE SCREEN CANDIDATES >= 800 LOC
============================================================
00001295 src/app/(app)/admin/reports/index.tsx
00001164 src/app/(app)/(tabs)/account.tsx
00000959 src/app/(app)/admin/warranties/[id].tsx
00000827 src/app/(app)/admin/catalog/create.tsx
LARGE_SCREEN_GE_800_LOC_COUNT=4

============================================================
2D. POSSIBLE NON-VIRTUALIZED LARGE LIST SCREENS
============================================================
00000449 maps=001 src/app/(app)/commissions/index.tsx
SCROLLVIEW_PLUS_MAP_CANDIDATE_COUNT=1

============================================================
2E. REACT QUERY CACHE POLICY CALL SITES
============================================================
src/features/admin/orders-admin-actions.tsx:218:    staleTime: 60_000,
src/app/(app)/(tabs)/catalog.tsx:31:    staleTime: 10 * 60_000
src/app/(app)/(tabs)/home.tsx:48:    staleTime: 60_000,
src/app/(app)/(tabs)/home.tsx:54:    staleTime: 60_000,
src/app/(app)/checkout.tsx:28:  const options = useQuery({ queryKey: ['order-options'], queryFn: api.orders.options, enabled: allowed && items.length > 0, staleTime: 5 * 60_000 });
src/app/(app)/admin/users/[id].tsx:62:    staleTime: 60_000,
src/app/(app)/admin/users/create.tsx:46:    staleTime: 60_000,
src/app/(app)/admin/settings/modules/index.tsx:32:    staleTime: 30_000,
src/app/(app)/admin/exchange-rate/index.tsx:68:    staleTime: 30_000,
src/app/(app)/admin/index.tsx:38:    staleTime: 60_000,
src/app/(app)/admin/search.tsx:49:    staleTime: 10_000,
src/app/(app)/admin/catalog/[id]/direct-sale.tsx:101:    staleTime: 30_000,
src/app/(app)/after-sales/create/[orderId].tsx:72:    staleTime: 60_000,
src/app/_layout.tsx:24:    queries: { staleTime: 45_000, retry: 1, refetchOnWindowFocus: false },

============================================================
3. API / NETWORK STATIC BASELINE
============================================================
API_CLIENT_LOC=273
API_CLIENT_BYTES=9092

============================================================
3A. API CLIENT TIMEOUT / FETCH / ABORT SIGNALS
============================================================
42:  timeoutMs?: number;
53:  const controller = new AbortController();
54:  const timeout = setTimeout(() => controller.abort(), options.timeoutMs ?? 20_000);
61:    if (token) headers.set('Authorization', `Bearer ${token}`);
70:      headers.set('Content-Type', 'application/json');
74:    const response = await fetch(`${API_URL}/${path.replace(/^\//, '')}`, {
107:      throw new ApiError(0, { message: 'Server nije odgovorio na vreme.', code: 'request_timeout' });
114:    clearTimeout(timeout);
123:  timeoutMs = 30_000,
126:  const controller = new AbortController();
127:  const timeout = setTimeout(() => controller.abort(), timeoutMs);
134:    if (token) headers.set('Authorization', 'Bearer ' + token);
171:        code: 'request_timeout',
179:    clearTimeout(timeout);
189:export async function apiDownload(path: string, timeoutMs = 30_000): Promise<ApiDownloadResult> {
191:  const controller = new AbortController();
192:  const timeout = setTimeout(() => controller.abort(), timeoutMs);
199:    if (token) headers.set('Authorization', `Bearer ${token}`);
201:    const response = await fetch(`${API_URL}/${path.replace(/^\//, '')}`, {
255:      throw new ApiError(0, { message: 'Preuzimanje je isteklo. Pokušaj ponovo.', code: 'request_timeout' });
262:    clearTimeout(timeout);
FETCH_REFERENCES=0
ABORT_CONTROLLER_REFERENCES=3
API_DOWNLOAD_REFERENCES=27
API_GET_REFERENCES=0
API_POST_REFERENCES=0
API_PATCH_REFERENCES=0
API_PUT_REFERENCES=0
API_DELETE_REFERENCES=0

============================================================
3B. QUERY KEYS + INVALIDATIONS
============================================================
src/features/admin/order-archive-admin.tsx:37:      await client.invalidateQueries({ queryKey: adminQueryKeys.adminOrdersRoot() });
src/features/admin/catalog-advanced-product-actions.tsx:34:      await client.invalidateQueries({ queryKey: adminQueryKeys.catalogAdvancedProduct(product.id) });
src/features/admin/orders-admin-actions.tsx:212:    queryKey: ['admin', 'orders', 'supplier-options'],
src/features/admin/orders-admin-actions.tsx:227:      await client.invalidateQueries({ queryKey: ['admin', 'orders'] });
src/features/admin/product-deletion-admin.tsx:46:    queryKey: ['admin', 'catalog', 'product', productId, 'deletion'],
src/features/catalog/product-image-manager.tsx:233:    await client.invalidateQueries({ queryKey: ['admin', 'catalog', 'product-images', productId] });
src/features/catalog/product-image-manager.tsx:245:      client.setQueryData(queryKey, response);
src/features/notifications/push-notification-bridge.tsx:64:      void queryClient.invalidateQueries({ queryKey: ['notifications'] });
src/features/notifications/push-notification-bridge.tsx:89:        await queryClient.invalidateQueries({ queryKey: ['devices'] });
src/app/(app)/(tabs)/notifications.tsx:31:  const query = useQuery({ queryKey: ['notifications'], queryFn: () => api.notifications.list(), enabled: allowed });
src/app/(app)/(tabs)/notifications.tsx:37:      client.setQueryData<PaginatedResponse<BusinessNotification>> (
src/app/(app)/(tabs)/notifications.tsx:73:      client.setQueryData<PaginatedResponse<BusinessNotification>>(
src/app/(app)/(tabs)/catalog.tsx:28:    queryKey: ['catalog-filters'],
src/app/(app)/(tabs)/catalog.tsx:34:    queryKey: ['products', { search, stock }],
src/app/(app)/(tabs)/home.tsx:45:    queryKey: adminQueryKeys.foundation(),
src/app/(app)/(tabs)/home.tsx:51:    queryKey: adminQueryKeys.managementReport(HOME_REPORT_PARAMS),
src/app/(app)/(tabs)/orders.tsx:24:  const query = useQuery({ queryKey: ['orders'], queryFn: () => api.orders.list(), enabled: allowed });
src/app/(app)/commissions/[id].tsx:36:    queryKey: ['commissions', commissionId],
src/app/(app)/commissions/index.tsx:113:    queryKey: ['commissions', params],
src/app/(app)/order/[id].tsx:83:    queryKey: ['order', orderId],
src/app/(app)/order/[id].tsx:89:    queryKey: ['order-post-create', orderId],
src/app/(app)/order/[id].tsx:97:      await client.invalidateQueries({ queryKey: ['orders'] });
src/app/(app)/order/[id].tsx:98:      await client.invalidateQueries({ queryKey: ['order', orderId] });
src/app/(app)/order/[id].tsx:99:      await client.invalidateQueries({ queryKey: ['order-post-create', orderId] });
src/app/(app)/order/[id].tsx:113:      await client.invalidateQueries({ queryKey: ['orders'] });
src/app/(app)/order/[id].tsx:114:      await client.invalidateQueries({ queryKey: ['order', orderId] });
src/app/(app)/order/[id].tsx:115:      await client.invalidateQueries({ queryKey: ['order-post-create', orderId] });
src/app/(app)/notification-settings.tsx:47:  const devices = useQuery({ queryKey: ['devices'], queryFn: api.devices.list, enabled: allowed && hasFeature('mobile_devices') });
src/app/(app)/notification-settings.tsx:81:        queryClient.invalidateQueries({ queryKey: ['devices'] }),
src/app/(app)/notification-settings.tsx:111:        await queryClient.invalidateQueries({ queryKey: ['devices'] });
src/app/(app)/checkout.tsx:28:  const options = useQuery({ queryKey: ['order-options'], queryFn: api.orders.options, enabled: allowed && items.length > 0, staleTime: 5 * 60_000 });
src/app/(app)/checkout.tsx:81:      await client.invalidateQueries({ queryKey: ['orders'] });
src/app/(app)/checkout.tsx:82:      await client.invalidateQueries({ queryKey: ['order-options'] });
src/app/(app)/product/[slug].tsx:35:  const query = useQuery({ queryKey: ['product', slug], queryFn: () => api.catalog.product(slug), enabled: allowed && Boolean(slug) });
src/app/(app)/admin/commissions/[id].tsx:61:    queryKey: adminQueryKeys.commission(commissionId),
src/app/(app)/admin/commissions/[id].tsx:70:      client.setQueryData(adminQueryKeys.commission(commissionId), updated);
src/app/(app)/admin/commissions/[id].tsx:71:      await client.invalidateQueries({ queryKey: adminQueryKeys.commissions() });
src/app/(app)/admin/commissions/index.tsx:87:    queryKey: adminQueryKeys.commissionsList(params),
src/app/(app)/admin/commissions/index.tsx:99:      await client.invalidateQueries({ queryKey: adminQueryKeys.commissions() });
src/app/(app)/admin/receivables/[id].tsx:120:    queryKey: adminQueryKeys.receivable(receivableId),
src/app/(app)/admin/receivables/[id].tsx:127:    await client.invalidateQueries({ queryKey: adminQueryKeys.receivables() });
src/app/(app)/admin/receivables/index.tsx:102:    queryKey: adminQueryKeys.receivablesList(applied),
src/app/(app)/admin/receivables/index.tsx:184:      await client.invalidateQueries({ queryKey: adminQueryKeys.receivables() });
src/app/(app)/admin/receivables/index.tsx:195:      await client.invalidateQueries({ queryKey: adminQueryKeys.receivables() });
src/app/(app)/admin/users/[id].tsx:53:    queryKey: adminQueryKeys.user(userId),
src/app/(app)/admin/users/[id].tsx:59:    queryKey: adminQueryKeys.userOptions(),
src/app/(app)/admin/users/[id].tsx:68:      client.setQueryData(adminQueryKeys.user(userId), {
src/app/(app)/admin/users/[id].tsx:72:      await client.invalidateQueries({ queryKey: adminQueryKeys.users() });
src/app/(app)/admin/users/create.tsx:43:    queryKey: adminQueryKeys.userOptions(),
src/app/(app)/admin/users/create.tsx:52:      await client.invalidateQueries({ queryKey: adminQueryKeys.users() });
src/app/(app)/admin/users/index.tsx:68:    queryKey: adminQueryKeys.usersList(applied),
src/app/(app)/admin/settings/modules/index.tsx:29:    queryKey: adminQueryKeys.moduleSettings(),
src/app/(app)/admin/settings/modules/index.tsx:53:      client.setQueryData(adminQueryKeys.moduleSettings(), response);
src/app/(app)/admin/settings/modules/index.tsx:56:        client.invalidateQueries({ queryKey: adminQueryKeys.foundation() }),
src/app/(app)/admin/settings/modules/index.tsx:66:      await client.invalidateQueries({ queryKey: adminQueryKeys.moduleSettings() });
src/app/(app)/admin/settings/turnstile.tsx:26:  const query = useQuery({ queryKey: adminQueryKeys.systemSettingsTurnstile(), queryFn: apiAdminSystemSettings.turnstile.state, enabled: allowed });
src/app/(app)/admin/settings/turnstile.tsx:32:  const mutation = useMutation({ mutationFn: () => apiAdminSystemSettings.turnstile.update({ turnstile_enabled: enabled, turnstile_site_key: siteKey, turnstile_secret_key: secretKey || undefined, turnstile_expected_hostname: hostname }), onSuccess: (response) => { client.setQueryData(adminQueryKeys.systemSettingsTurnstile(), response); setSecretKey(''); feedback.notify({ tone: 'success', title: 'Turnstile je sačuvan' }); }, onError: (error) => feedback.notify({ tone: 'danger', title: 'Turnstile nije sačuvan', message: error instanceof Error ? error.message : 'Greška.' }) });
src/app/(app)/admin/settings/documents.tsx:21:  const query = useQuery({ queryKey: adminQueryKeys.systemSettingsDocuments(), queryFn: apiAdminSystemSettings.documents.state, enabled: allowed });
src/app/(app)/admin/settings/documents.tsx:23:  const save = useMutation({ mutationFn: async () => { const form = new FormData(); ['documents_company_name','documents_company_address','documents_company_city','documents_company_tax_id','documents_company_registration_number','documents_company_phone','documents_company_email','documents_company_website','documents_vat_rate','documents_payment_due_days','documents_default_note','documents_footer_note'].forEach((key)=>form.append(key,values[key]??'')); form.append('documents_vat_enabled',values.documents_vat_enabled==='1'?'1':'0'); if(logo) appendAdminSystemFile(form,'documents_logo',logo); return apiAdminSystemSettings.documents.update(form); }, onSuccess:(response)=>{client.setQueryData(adminQueryKeys.systemSettingsDocuments(),response);feedback.notify({tone:'success',title:'Dokument podešavanja su sačuvana'});},onError:(error)=>feedback.notify({tone:'danger',title:'Podešavanja nisu sačuvana',message:error instanceof Error?error.message:'Greška.'}) });
src/app/(app)/admin/settings/documents.tsx:24:  const remove = useMutation({ mutationFn: apiAdminSystemSettings.documents.removeLogo, onSuccess:(response)=>client.setQueryData(adminQueryKeys.systemSettingsDocuments(),response), onError:(error)=>feedback.notify({tone:'danger',title:'Logo nije uklonjen',message:error instanceof Error?error.message:'Greška.'}) });
src/app/(app)/admin/settings/automation.tsx:35:  const query = useQuery({ queryKey: adminQueryKeys.systemSettingsAutomation(), queryFn: apiAdminSystemSettings.automation.state, enabled: allowed });
src/app/(app)/admin/settings/automation.tsx:50:    onSuccess: (response) => { client.setQueryData(adminQueryKeys.systemSettingsAutomation(), response); feedback.notify({ tone: 'success', title: 'Automatizacija je ažurirana' }); },
src/app/(app)/admin/settings/order-emails.tsx:27:  const query = useQuery({ queryKey: adminQueryKeys.systemSettingsOrderEmails(), queryFn: apiAdminSystemSettings.orderEmails.state, enabled: allowed });
src/app/(app)/admin/settings/order-emails.tsx:29:  const save = useMutation({ mutationFn: () => apiAdminSystemSettings.orderEmails.update({ ...values, ...Object.fromEntries(FLAGS.map(([key]) => [key, values[key] === '1'])) }), onSuccess: (response) => { client.setQueryData(adminQueryKeys.systemSettingsOrderEmails(), response); feedback.notify({ tone: 'success', title: 'E-mail podešavanja su sačuvana' }); }, onError: (error) => feedback.notify({ tone: 'danger', title: 'Podešavanja nisu sačuvana', message: error instanceof Error ? error.message : 'Greška.' }) });
src/app/(app)/admin/settings/order-emails.tsx:30:  const action = useMutation({ mutationFn: (kind: 'dispatch'|'retry') => kind === 'dispatch' ? apiAdminSystemSettings.orderEmails.dispatch() : apiAdminSystemSettings.orderEmails.retry(), onSuccess: (response) => { client.setQueryData(adminQueryKeys.systemSettingsOrderEmails(), response); feedback.notify({ tone: 'success', title: response.message ?? 'Outbox je ažuriran' }); }, onError: (error) => feedback.notify({ tone: 'danger', title: 'Outbox akcija nije uspela', message: error instanceof Error ? error.message : 'Greška.' }) });
src/app/(app)/admin/settings/bank-accounts.tsx:23:  const query=useQuery({queryKey:adminQueryKeys.systemSettingsBankAccounts(),queryFn:apiAdminSystemSettings.bankAccounts.state,enabled:allowed});
src/app/(app)/admin/settings/bank-accounts.tsx:25:  const save=useMutation({mutationFn:()=>editing===null?apiAdminSystemSettings.bankAccounts.create(form):apiAdminSystemSettings.bankAccounts.update(editing,form),onSuccess:(response)=>{client.setQueryData(adminQueryKeys.systemSettingsBankAccounts(),response);setEditing(null);setForm(EMPTY);feedback.notify({tone:'success',title:'Žiro račun je sačuvan'});},onError:(error)=>feedback.notify({tone:'danger',title:'Račun nije sačuvan',message:error instanceof Error?error.message:'Greška.'})});
src/app/(app)/admin/settings/bank-accounts.tsx:26:  const remove=useMutation({mutationFn:apiAdminSystemSettings.bankAccounts.remove,onSuccess:(response)=>client.setQueryData(adminQueryKeys.systemSettingsBankAccounts(),response),onError:(error)=>feedback.notify({tone:'danger',title:'Račun nije obrisan',message:error instanceof Error?error.message:'Greška.'})});
src/app/(app)/admin/settings/appearance.tsx:29:  const query = useQuery({ queryKey: adminQueryKeys.systemSettingsAppearance(), queryFn: apiAdminSystemSettings.appearance.state, enabled: allowed });
src/app/(app)/admin/settings/appearance.tsx:62:    onSuccess: (response) => { client.setQueryData(adminQueryKeys.systemSettingsAppearance(), response); feedback.notify({ tone: 'success', title: 'Izgled sajta je sačuvan' }); },
src/app/(app)/admin/settings/appearance.tsx:65:  const removeMutation = useMutation({ mutationFn: apiAdminSystemSettings.appearance.removeAsset, onSuccess: (response) => client.setQueryData(adminQueryKeys.systemSettingsAppearance(), response), onError: (error) => feedback.notify({ tone: 'danger', title: 'Fajl nije uklonjen', message: error instanceof Error ? error.message : 'Greška.' }) });
src/app/(app)/admin/exchange-rate/index.tsx:65:    queryKey: adminQueryKeys.exchangeRate(),
src/app/(app)/admin/exchange-rate/index.tsx:74:      client.setQueryData(adminQueryKeys.exchangeRate(), response);
src/app/(app)/admin/exchange-rate/index.tsx:82:      await client.invalidateQueries({ queryKey: adminQueryKeys.exchangeRate() });
src/app/(app)/admin/customer-portal/conversations/[id].tsx:47:      client.setQueryData(queryKey, data);
src/app/(app)/admin/customer-portal/conversations/[id].tsx:48:      await client.invalidateQueries({ queryKey: adminQueryKeys.customerPortalRoot() });
src/app/(app)/admin/customer-portal/conversations/[id].tsx:60:      client.setQueryData(queryKey, data);
src/app/(app)/admin/customer-portal/conversations/[id].tsx:64:      await client.invalidateQueries({ queryKey: adminQueryKeys.customerPortalRoot() });
src/app/(app)/admin/customer-portal/[userId].tsx:41:    await client.invalidateQueries({ queryKey: adminQueryKeys.customerPortalUserRoot(userId) });
src/app/(app)/admin/customer-portal/[userId].tsx:42:    await client.invalidateQueries({ queryKey: adminQueryKeys.customerPortalRoot() });
src/app/(app)/admin/customer-portal/index.tsx:37:    queryKey: adminQueryKeys.customerPortal({ q, status }),
src/app/(app)/admin/customer-portal/index.tsx:51:      await client.invalidateQueries({ queryKey: adminQueryKeys.customerPortalRoot() });
src/app/(app)/admin/orders/[id].tsx:91:    queryKey: adminQueryKeys.adminOrder(orderId),
src/app/(app)/admin/orders/archived.tsx:53:    queryKey: adminQueryKeys.adminOrderArchives(params),
src/app/(app)/admin/orders/archived.tsx:59:    await client.invalidateQueries({ queryKey: adminQueryKeys.adminOrdersRoot() });
src/app/(app)/admin/orders/index.tsx:90:    queryKey: adminQueryKeys.adminOrders(applied),
src/app/(app)/admin/user-groups/index.tsx:118:    queryKey: adminQueryKeys.userGroups(applied),
src/app/(app)/admin/user-groups/index.tsx:128:      await queryClient.invalidateQueries({ queryKey: adminQueryKeys.userGroupsRoot() });
src/app/(app)/admin/user-groups/index.tsx:147:      await queryClient.invalidateQueries({ queryKey: adminQueryKeys.userGroupsRoot() });
src/app/(app)/admin/reports/index.tsx:162:    queryKey: adminQueryKeys.managementReport(applied),
src/app/(app)/admin/reports/index.tsx:484:    queryKey: adminQueryKeys.reportSchedules(),
src/app/(app)/admin/reports/index.tsx:489:    await client.invalidateQueries({ queryKey: adminQueryKeys.reportSchedules() });
src/app/(app)/admin/service-parts/index.tsx:99:    queryKey: adminQueryKeys.servicePartsList(applied),
src/app/(app)/admin/service-parts/index.tsx:131:    await client.invalidateQueries({ queryKey: adminQueryKeys.serviceParts() });
src/app/(app)/admin/service-parts/purchases/[id].tsx:38:  const query = useQuery({ queryKey: adminQueryKeys.servicePartPurchase(purchaseId), queryFn: () => apiAdminServiceParts.purchaseDetail(purchaseId), enabled: allowed && Number.isInteger(purchaseId) && purchaseId > 0 });
src/app/(app)/admin/service-parts/purchases/[id].tsx:55:      await client.invalidateQueries({ queryKey: adminQueryKeys.servicePartPurchasesRoot() });
src/app/(app)/admin/service-parts/purchases/[id].tsx:56:      await client.invalidateQueries({ queryKey: adminQueryKeys.servicePartPurchase(purchaseId) });
src/app/(app)/admin/service-parts/purchases/index.tsx:63:  const query = useQuery({ queryKey: adminQueryKeys.servicePartPurchases(applied), queryFn: () => apiAdminServiceParts.purchasesList(applied), enabled: allowed });
src/app/(app)/admin/service-parts/purchases/index.tsx:84:      await client.invalidateQueries({ queryKey: adminQueryKeys.servicePartPurchasesRoot() });
src/app/(app)/admin/service-parts/suppliers.tsx:48:  const query = useQuery({ queryKey: adminQueryKeys.servicePartSuppliers(applied), queryFn: () => apiAdminServiceParts.suppliersList(applied), enabled: allowed });
src/app/(app)/admin/service-parts/suppliers.tsx:64:  const refresh = async () => client.invalidateQueries({ queryKey: adminQueryKeys.servicePartSuppliersRoot() });
src/app/(app)/admin/audit/[id].tsx:48:    queryKey: adminQueryKeys.auditEvent(eventId),
src/app/(app)/admin/audit/index.tsx:71:    queryKey: adminQueryKeys.auditEvents(applied),
src/app/(app)/admin/inventory/index.tsx:132:    queryKey: adminQueryKeys.inventoryList(applied),
src/app/(app)/admin/inventory/index.tsx:138:    queryKey: adminQueryKeys.inventoryLookup(lookupQ.trim()),
src/app/(app)/admin/inventory/index.tsx:144:    queryKey: adminQueryKeys.inventoryMovements(movementApplied),
src/app/(app)/admin/inventory/index.tsx:159:    await client.invalidateQueries({ queryKey: adminQueryKeys.inventory() });
src/app/(app)/admin/warranties/[id].tsx:136:    queryKey: adminQueryKeys.warranty(warrantyId),
src/app/(app)/admin/warranties/[id].tsx:158:    client.setQueryData(
src/app/(app)/admin/warranties/[id].tsx:163:    await client.invalidateQueries({
src/app/(app)/admin/warranties/[id].tsx:164:      queryKey: adminQueryKeys.warranties(),
src/app/(app)/admin/warranties/rules.tsx:117:    queryKey: adminQueryKeys.warrantyRules(),
src/app/(app)/admin/warranties/rules.tsx:229:        client.invalidateQueries({ queryKey: adminQueryKeys.warrantyRules() }),
src/app/(app)/admin/warranties/rules.tsx:230:        client.invalidateQueries({ queryKey: adminQueryKeys.warranties() }),
src/app/(app)/admin/warranties/rules.tsx:252:      await client.invalidateQueries({ queryKey: adminQueryKeys.warranties() });
src/app/(app)/admin/warranties/index.tsx:90:    queryKey: adminQueryKeys.warrantiesList(params),
src/app/(app)/admin/field-operations/[id].tsx:150:    queryKey: adminQueryKeys.fieldWorkOrder(workOrderId),
src/app/(app)/admin/field-operations/[id].tsx:157:    await client.invalidateQueries({ queryKey: adminQueryKeys.fieldOperations() });
src/app/(app)/admin/field-operations/index.tsx:86:    queryKey: adminQueryKeys.fieldOperationsList(applied),
src/app/(app)/admin/system-health/index.tsx:92:    queryKey: adminQueryKeys.systemHealth(),
src/app/(app)/admin/after-sales/[id].tsx:116:    queryKey: adminQueryKeys.afterSalesAdminCase(caseId),
src/app/(app)/admin/after-sales/[id].tsx:124:    await client.invalidateQueries({ queryKey: adminQueryKeys.afterSalesAdmin() });
src/app/(app)/admin/after-sales/index.tsx:96:    queryKey: adminQueryKeys.afterSalesAdminList(applied),
src/app/(app)/admin/index.tsx:35:    queryKey: adminQueryKeys.foundation(),
src/app/(app)/admin/search.tsx:46:    queryKey: adminQueryKeys.globalSearch(searchQuery),
src/app/(app)/admin/catalog/[id]/clone.tsx:51:    queryKey: adminQueryKeys.catalogAdvancedProduct(productId),
src/app/(app)/admin/catalog/[id]/clone.tsx:89:      await client.invalidateQueries({ queryKey: adminQueryKeys.catalogAdvancedRoot() });
src/app/(app)/admin/catalog/[id]/direct-sale.tsx:98:    queryKey: ['admin', 'catalog', 'direct-sale', productId],
src/app/(app)/admin/catalog/[id]/direct-sale.tsx:117:        client.invalidateQueries({ queryKey: ['admin'] }),
src/app/(app)/admin/catalog/[id]/direct-sale.tsx:118:        client.invalidateQueries({ queryKey: ['products'] }),
src/app/(app)/admin/catalog/dictionaries/product-types/[id].tsx:60:    queryKey: validId ? adminQueryKeys.dictionaryProductType(productTypeId) : ['admin', 'catalog', 'dictionaries', 'product-types', 'invalid'],
src/app/(app)/admin/catalog/dictionaries/product-types/[id].tsx:99:      client.invalidateQueries({ queryKey: adminQueryKeys.dictionaryProductType(productTypeId) }),
src/app/(app)/admin/catalog/dictionaries/product-types/[id].tsx:100:      client.invalidateQueries({ queryKey: adminQueryKeys.dictionary('product-types') }),
src/app/(app)/admin/catalog/dictionaries/[resource].tsx:86:    queryKey: resource ? adminQueryKeys.dictionary(resource) : ['admin', 'catalog', 'dictionaries', 'invalid'],
src/app/(app)/admin/catalog/dictionaries/[resource].tsx:93:    await client.invalidateQueries({ queryKey: adminQueryKeys.dictionary(resource) });
src/app/(app)/admin/catalog/[id].tsx:136:    queryKey: ['admin-catalog-create-options'],
src/app/(app)/admin/catalog/[id].tsx:141:    queryKey: ['admin', 'catalog', 'product', productId],
src/app/(app)/admin/catalog/[id].tsx:198:      client.invalidateQueries({ queryKey: ['admin', 'catalog'] }),
src/app/(app)/admin/catalog/[id].tsx:199:      client.invalidateQueries({ queryKey: ['products'] }),
src/app/(app)/admin/catalog/[id].tsx:200:      client.invalidateQueries({ queryKey: ['catalog-filters'] }),
src/app/(app)/admin/catalog/create.tsx:134:    queryKey: ['admin-catalog-create-options'],
src/app/(app)/admin/catalog/create.tsx:178:        client.invalidateQueries({ queryKey: ['products'] }),
src/app/(app)/admin/catalog/create.tsx:179:        client.invalidateQueries({ queryKey: ['catalog-filters'] }),
src/app/(app)/admin/catalog/create.tsx:180:        client.invalidateQueries({ queryKey: ['admin-catalog-create-options'] }),
src/app/(app)/admin/catalog/data-quality/index.tsx:52:    queryKey: adminQueryKeys.dataQuality(),
src/app/(app)/admin/catalog/data-quality/index.tsx:60:      await client.invalidateQueries({ queryKey: adminQueryKeys.dataQuality() });
src/app/(app)/admin/catalog/bulk/index.tsx:72:    queryKey: adminQueryKeys.catalogAdvancedProducts(search),
src/app/(app)/admin/catalog/bulk/index.tsx:77:    queryKey: adminQueryKeys.catalogBulkOptions(),
src/app/(app)/admin/catalog/bulk/index.tsx:111:      await client.invalidateQueries({ queryKey: adminQueryKeys.catalogAdvancedRoot() });
src/app/(app)/admin/catalog/purchase-costs.tsx:33:    queryKey: adminQueryKeys.purchaseCosts(showAll),
src/app/(app)/admin/catalog/purchase-costs.tsx:44:        client.invalidateQueries({ queryKey: ['admin', 'catalog', 'purchase-costs'] }),
src/app/(app)/admin/catalog/purchase-costs.tsx:45:        client.invalidateQueries({ queryKey: adminQueryKeys.foundation() }),
src/app/(app)/admin/catalog/purchase-costs.tsx:46:        client.invalidateQueries({ queryKey: ['admin', 'catalog'] }),
src/app/(app)/admin/catalog/index.tsx:84:    queryKey: ['admin', 'catalog', 'products', mode, search, status, page],
src/app/(app)/admin/catalog/brands/index.tsx:53:    queryKey: adminQueryKeys.brandsList(filters),
src/app/(app)/admin/catalog/brands/index.tsx:58:    queryKey: adminQueryKeys.brandOptions(),
src/app/(app)/admin/catalog/brands/index.tsx:70:        client.invalidateQueries({ queryKey: ['admin', 'brands'] }),
src/app/(app)/admin/catalog/brands/index.tsx:71:        client.invalidateQueries({ queryKey: ['admin', 'catalog'] }),
src/app/(app)/admin/catalog/brands/index.tsx:85:      await client.invalidateQueries({ queryKey: ['admin', 'brands'] });
src/app/(app)/admin/couriers/index.tsx:34:  const query = useQuery({ queryKey: ['admin', 'couriers'], queryFn: apiAdminCouriers.list, enabled: allowed });
src/app/(app)/admin/couriers/index.tsx:41:        client.invalidateQueries({ queryKey: ['admin', 'couriers'] }),
src/app/(app)/admin/couriers/index.tsx:42:        client.invalidateQueries({ queryKey: ['admin', 'orders'] }),
src/app/(app)/warranties/[id].tsx:50:    queryKey: ['warranties', warrantyId],
src/app/(app)/warranties/index.tsx:86:    queryKey: ['warranties'],
src/app/(app)/portal/messages/[id].tsx:31:      client.setQueryData(queryKey, data);
src/app/(app)/portal/messages/[id].tsx:32:      await client.invalidateQueries({ queryKey: ['portal', 'messages'] });
src/app/(app)/portal/messages/index.tsx:31:  const query = useQuery({ queryKey: portalKey, queryFn: apiPortal.list, enabled: allowed });
src/app/(app)/portal/messages/index.tsx:38:      await client.invalidateQueries({ queryKey: portalKey });
src/app/(app)/devices.tsx:29:  const query = useQuery({ queryKey: ['devices'], queryFn: api.devices.list, enabled: allowed });
src/app/(app)/devices.tsx:30:  const revoke = useMutation({ mutationFn: api.devices.revoke, onSuccess: async (_, id) => { const current = query.data?.find((item) => item.id === id)?.is_current; if (current) await signOut(); else await client.invalidateQueries({ queryKey: ['devices'] }); } });
src/app/(app)/assigned-orders/[id].tsx:23:    queryKey: ['assigned-order', orderId],
src/app/(app)/assigned-orders/index.tsx:22:    queryKey: ['assigned-orders'],
src/app/(app)/after-sales/[id].tsx:74:    queryKey: ['after-sales', caseId],
src/app/(app)/after-sales/[id].tsx:84:      void client.invalidateQueries({ queryKey: ['after-sales'] });
src/app/(app)/after-sales/create/[orderId].tsx:69:    queryKey: ['after-sales-options', orderId],
src/app/(app)/after-sales/create/[orderId].tsx:113:      await client.invalidateQueries({ queryKey: ['after-sales'] });
src/app/(app)/after-sales/create/[orderId].tsx:114:      await client.invalidateQueries({ queryKey: ['after-sales-options', orderId] });
src/app/(app)/after-sales/index.tsx:68:    queryKey: ['after-sales'],
src/app/(app)/sessions.tsx:25:  const query = useQuery({ queryKey: ['account', 'sessions'], queryFn: api.account.sessions });
src/app/(app)/sessions.tsx:35:      await queryClient.invalidateQueries({ queryKey: ['account', 'sessions'] });
src/app/(app)/sessions.tsx:42:      await queryClient.invalidateQueries({ queryKey: ['account', 'sessions'] });

============================================================
3C. SECURESTORE ACCESS CALL SITES
============================================================
src/lib/storage.ts:14:const secureOptions: SecureStore.SecureStoreOptions = {
src/lib/storage.ts:15:  keychainAccessible: SecureStore.WHEN_UNLOCKED_THIS_DEVICE_ONLY
src/lib/storage.ts:19:  get: () => SecureStore.getItemAsync(TOKEN_KEY, secureOptions),
src/lib/storage.ts:20:  set: (token: string) => SecureStore.setItemAsync(TOKEN_KEY, token, secureOptions),
src/lib/storage.ts:21:  clear: () => SecureStore.deleteItemAsync(TOKEN_KEY, secureOptions)
src/lib/storage.ts:25:  const existing = await SecureStore.getItemAsync(INSTALLATION_KEY, secureOptions);
src/lib/storage.ts:29:  await SecureStore.setItemAsync(INSTALLATION_KEY, generated, secureOptions);
src/lib/storage.ts:34:  await SecureStore.setItemAsync(DEVICE_ID_KEY, String(id), secureOptions);
src/lib/storage.ts:38:  const value = await SecureStore.getItemAsync(DEVICE_ID_KEY, secureOptions);
src/lib/storage.ts:44:  await SecureStore.deleteItemAsync(DEVICE_ID_KEY, secureOptions);
src/lib/storage.ts:55:  const raw = await SecureStore.getItemAsync(userPreferencesKey(userId), secureOptions);
src/lib/storage.ts:74:  await SecureStore.setItemAsync(

============================================================
4. CANONICAL BUILD13 BINARY BASELINE
============================================================
BUILD13_AAB_PATH=/home/icaffeco/backups/current/ALD1N-CLEAN-STABLE-v1.0.0-cms-v2.2.0-20260828-112307/android/Ald1n-CMS-v1.0.0-production-vc13-97859c47-1199-4a82-b782-00d28f6f98c1.aab
BUILD13_AAB_SHA256=1b0df12a82aecb3fd36391c6050af45ff939f4a634b2675963695d006d745937
BUILD13_AAB_SIZE_BYTES=79125571
BUILD13_AAB_SIZE_MIB=75.460
AAB_ZIP_ENTRY_COUNT=1460

============================================================
4A. LARGEST AAB ENTRIES BY UNCOMPRESSED SIZE
============================================================
249448884 
13661184 base/dex/classes.dex
11519252 base/dex/classes4.dex
11353552 BUNDLE-METADATA/com.android.tools.build.debugsymbols/arm64-v8a/libreactnative.so.sym
10844052 BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86/libreactnative.so.sym
10756120 BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86_64/libreactnative.so.sym
10003252 base/dex/classes2.dex
9157616 base/dex/classes3.dex
8810804 BUNDLE-METADATA/com.android.tools.build.debugsymbols/armeabi-v7a/libreactnative.so.sym
7362736 base/lib/x86/libreactnative.so
7098048 base/lib/x86_64/libreactnative.so
6985168 base/lib/arm64-v8a/libreactnative.so
6723600 base/dex/classes5.dex
6185872 base/assets/index.android.bundle
4859120 base/lib/armeabi-v7a/libreactnative.so
3019744 base/lib/x86/libhermesvm.so
2707024 BUNDLE-METADATA/com.android.tools.build.debugsymbols/arm64-v8a/libreanimated.so.sym
2591976 base/lib/x86_64/libhermesvm.so
2548816 BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86_64/libreanimated.so.sym
2477320 base/lib/arm64-v8a/libhermesvm.so
2381936 BUNDLE-METADATA/com.android.tools.build.debugsymbols/arm64-v8a/libexpo-modules-core.so.sym
2379700 BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86/libreanimated.so.sym
2256762 base/resources.pb
2208776 BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86_64/libexpo-modules-core.so.sym
2115440 BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86/libexpo-modules-core.so.sym
2070428 BUNDLE-METADATA/com.android.tools.build.debugsymbols/armeabi-v7a/libreanimated.so.sym
1950176 BUNDLE-METADATA/com.android.tools.build.debugsymbols/arm64-v8a/libreact_codegen_rnscreens.so.sym
1898008 BUNDLE-METADATA/com.android.tools.build.debugsymbols/armeabi-v7a/libexpo-modules-core.so.sym
1891872 BUNDLE-METADATA/com.android.tools.build.debugsymbols/arm64-v8a/libworklets.so.sym
1860264 BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86_64/libreact_codegen_rnscreens.so.sym
1849964 BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86/libreact_codegen_rnscreens.so.sym
1770920 BUNDLE-METADATA/com.android.tools.build.debugsymbols/arm64-v8a/libc++_shared.so.sym
1735656 BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86_64/libworklets.so.sym
1700248 base/lib/armeabi-v7a/libhermesvm.so
1647408 BUNDLE-METADATA/com.android.tools.build.debugsymbols/arm64-v8a/libappmodules.so.sym
1614120 BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86/libworklets.so.sym
1609312 BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86_64/libc++_shared.so.sym
1574184 BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86/libc++_shared.so.sym
1555696 BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86_64/libappmodules.so.sym
1547196 BUNDLE-METADATA/com.android.tools.build.debugsymbols/armeabi-v7a/libreact_codegen_rnscreens.so.sym
1538464 base/lib/x86_64/libreanimated.so
1526864 BUNDLE-METADATA/com.android.tools.build.debugsymbols/arm64-v8a/libNitroModules.so.sym
1514888 BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86/libappmodules.so.sym
1500888 base/lib/arm64-v8a/libreanimated.so
1500152 BUNDLE-METADATA/com.android.tools.build.debugsymbols/arm64-v8a/libNitroGoogleSignin.so.sym
1482616 base/lib/arm64-v8a/libexpo-modules-core.so
1477620 BUNDLE-METADATA/com.android.tools.build.debugsymbols/armeabi-v7a/libworklets.so.sym
1443872 base/lib/x86_64/libexpo-modules-core.so
1429360 BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86_64/libNitroModules.so.sym
1428124 base/lib/x86/libreanimated.so

============================================================
4B. JS / HERMES / DEX / NATIVE LIB AAB ENTRIES
============================================================
  1500152  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/arm64-v8a/libNitroGoogleSignin.so.sym
  1526864  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/arm64-v8a/libNitroModules.so.sym
  1647408  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/arm64-v8a/libappmodules.so.sym
  1770920  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/arm64-v8a/libc++_shared.so.sym
  2381936  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/arm64-v8a/libexpo-modules-core.so.sym
   119352  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/arm64-v8a/libexpo-updates.so.sym
   278400  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/arm64-v8a/libfbjni.so.sym
    53440  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/arm64-v8a/libgesturehandler.so.sym
   553048  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/arm64-v8a/libjsi.so.sym
  1950176  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/arm64-v8a/libreact_codegen_rnscreens.so.sym
   237728  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/arm64-v8a/libreact_codegen_safeareacontext.so.sym
 11353552  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/arm64-v8a/libreactnative.so.sym
  2707024  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/arm64-v8a/libreanimated.so.sym
   162200  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/arm64-v8a/librnscreens.so.sym
  1891872  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/arm64-v8a/libworklets.so.sym
  1206364  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/armeabi-v7a/libNitroGoogleSignin.so.sym
  1214248  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/armeabi-v7a/libNitroModules.so.sym
  1339784  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/armeabi-v7a/libappmodules.so.sym
  1277420  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/armeabi-v7a/libc++_shared.so.sym
  1898008  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/armeabi-v7a/libexpo-modules-core.so.sym
    72476  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/armeabi-v7a/libexpo-updates.so.sym
   210220  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/armeabi-v7a/libfbjni.so.sym
    15488  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/armeabi-v7a/libgesturehandler.so.sym
   409880  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/armeabi-v7a/libjsi.so.sym
  1547196  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/armeabi-v7a/libreact_codegen_rnscreens.so.sym
   165860  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/armeabi-v7a/libreact_codegen_safeareacontext.so.sym
  8810804  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/armeabi-v7a/libreactnative.so.sym
  2070428  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/armeabi-v7a/libreanimated.so.sym
   101852  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/armeabi-v7a/librnscreens.so.sym
  1477620  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/armeabi-v7a/libworklets.so.sym
  1335204  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86/libNitroGoogleSignin.so.sym
  1378828  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86/libNitroModules.so.sym
  1514888  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86/libappmodules.so.sym
  1574184  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86/libc++_shared.so.sym
  2115440  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86/libexpo-modules-core.so.sym
   118316  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86/libexpo-updates.so.sym
   240736  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86/libfbjni.so.sym
    44280  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86/libgesturehandler.so.sym
   532332  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86/libjsi.so.sym
  1849964  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86/libreact_codegen_rnscreens.so.sym
   213264  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86/libreact_codegen_safeareacontext.so.sym
 10844052  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86/libreactnative.so.sym
  2379700  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86/libreanimated.so.sym
   138972  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86/librnscreens.so.sym
  1614120  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86/libworklets.so.sym
  1399480  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86_64/libNitroGoogleSignin.so.sym
  1429360  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86_64/libNitroModules.so.sym
  1555696  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86_64/libappmodules.so.sym
  1609312  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86_64/libc++_shared.so.sym
  2208776  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86_64/libexpo-modules-core.so.sym
   125984  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86_64/libexpo-updates.so.sym
   254704  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86_64/libfbjni.so.sym
    46616  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86_64/libgesturehandler.so.sym
   526224  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86_64/libjsi.so.sym
  1860264  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86_64/libreact_codegen_rnscreens.so.sym
   224504  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86_64/libreact_codegen_safeareacontext.so.sym
 10756120  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86_64/libreactnative.so.sym
  2548816  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86_64/libreanimated.so.sym
   147160  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86_64/librnscreens.so.sym
  1735656  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.debugsymbols/x86_64/libworklets.so.sym
       57  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.gradle/app-metadata.properties
    24885  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.libraries/dependencies.pb
    11055  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.profiles/baseline.prof
      576  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools.build.profiles/baseline.profm
       79  01-01-1981 01:01   BUNDLE-METADATA/com.android.tools/d8.json
      656  01-01-1981 01:01   BundleConfig.pb
     1720  01-01-1981 01:01   base/assets/app.config
     8877  01-01-1981 01:01   base/assets/app.manifest
     1310  01-01-1981 01:01   base/assets/expo-root.pem
  6185872  01-01-1981 01:01   base/assets/index.android.bundle
 13661184  01-01-1981 01:01   base/dex/classes.dex
 10003252  01-01-1981 01:01   base/dex/classes2.dex
  9157616  01-01-1981 01:01   base/dex/classes3.dex
 11519252  01-01-1981 01:01   base/dex/classes4.dex
  6723600  01-01-1981 01:01   base/dex/classes5.dex
   913720  01-01-1981 01:01   base/lib/arm64-v8a/libNitroGoogleSignin.so
   980312  01-01-1981 01:01   base/lib/arm64-v8a/libNitroModules.so
    10096  01-01-1981 01:01   base/lib/arm64-v8a/libandroidx.graphics.path.so
  1002416  01-01-1981 01:01   base/lib/arm64-v8a/libappmodules.so
  1292904  01-01-1981 01:01   base/lib/arm64-v8a/libc++_shared.so
     7112  01-01-1981 01:01   base/lib/arm64-v8a/libdatastore_shared_counter.so
  1482616  01-01-1981 01:01   base/lib/arm64-v8a/libexpo-modules-core.so
   101912  01-01-1981 01:01   base/lib/arm64-v8a/libexpo-updates.so
   177000  01-01-1981 01:01   base/lib/arm64-v8a/libfbjni.so
    38456  01-01-1981 01:01   base/lib/arm64-v8a/libgesturehandler.so
   318992  01-01-1981 01:01   base/lib/arm64-v8a/libgifimage.so
   141432  01-01-1981 01:01   base/lib/arm64-v8a/libhermestooling.so
  2477320  01-01-1981 01:01   base/lib/arm64-v8a/libhermesvm.so
     8760  01-01-1981 01:01   base/lib/arm64-v8a/libimagepipeline.so
   412696  01-01-1981 01:01   base/lib/arm64-v8a/libjsi.so
    23712  01-01-1981 01:01   base/lib/arm64-v8a/libnative-filters.so
   585376  01-01-1981 01:01   base/lib/arm64-v8a/libnative-imagetranscoder.so
  1200424  01-01-1981 01:01   base/lib/arm64-v8a/libreact_codegen_rnscreens.so
   159208  01-01-1981 01:01   base/lib/arm64-v8a/libreact_codegen_safeareacontext.so
  6985168  01-01-1981 01:01   base/lib/arm64-v8a/libreactnative.so
  1500888  01-01-1981 01:01   base/lib/arm64-v8a/libreanimated.so
   100904  01-01-1981 01:01   base/lib/arm64-v8a/librnscreens.so
   501856  01-01-1981 01:01   base/lib/arm64-v8a/libstatic-webp.so
  1101120  01-01-1981 01:01   base/lib/arm64-v8a/libworklets.so
   731872  01-01-1981 01:01   base/lib/arm64-v8a/libzstd-kmp.so
   655448  01-01-1981 01:01   base/lib/armeabi-v7a/libNitroGoogleSignin.so
   696832  01-01-1981 01:01   base/lib/armeabi-v7a/libNitroModules.so
     7252  01-01-1981 01:01   base/lib/armeabi-v7a/libandroidx.graphics.path.so
   748388  01-01-1981 01:01   base/lib/armeabi-v7a/libappmodules.so
   872872  01-01-1981 01:01   base/lib/armeabi-v7a/libc++_shared.so
     4416  01-01-1981 01:01   base/lib/armeabi-v7a/libdatastore_shared_counter.so
  1052464  01-01-1981 01:01   base/lib/armeabi-v7a/libexpo-modules-core.so
    60824  01-01-1981 01:01   base/lib/armeabi-v7a/libexpo-updates.so
   119456  01-01-1981 01:01   base/lib/armeabi-v7a/libfbjni.so
     8420  01-01-1981 01:01   base/lib/armeabi-v7a/libgesturehandler.so
   184380  01-01-1981 01:01   base/lib/armeabi-v7a/libgifimage.so
    76548  01-01-1981 01:01   base/lib/armeabi-v7a/libhermestooling.so
  1700248  01-01-1981 01:01   base/lib/armeabi-v7a/libhermesvm.so
     5876  01-01-1981 01:01   base/lib/armeabi-v7a/libimagepipeline.so
   289716  01-01-1981 01:01   base/lib/armeabi-v7a/libjsi.so
    12588  01-01-1981 01:01   base/lib/armeabi-v7a/libnative-filters.so
   341072  01-01-1981 01:01   base/lib/armeabi-v7a/libnative-imagetranscoder.so
   853708  01-01-1981 01:01   base/lib/armeabi-v7a/libreact_codegen_rnscreens.so
    97972  01-01-1981 01:01   base/lib/armeabi-v7a/libreact_codegen_safeareacontext.so
  4859120  01-01-1981 01:01   base/lib/armeabi-v7a/libreactnative.so
   965552  01-01-1981 01:01   base/lib/armeabi-v7a/libreanimated.so
    51704  01-01-1981 01:01   base/lib/armeabi-v7a/librnscreens.so
   367592  01-01-1981 01:01   base/lib/armeabi-v7a/libstatic-webp.so
   730468  01-01-1981 01:01   base/lib/armeabi-v7a/libworklets.so
   566996  01-01-1981 01:01   base/lib/armeabi-v7a/libzstd-kmp.so
   853880  01-01-1981 01:01   base/lib/x86/libNitroGoogleSignin.so
   944028  01-01-1981 01:01   base/lib/x86/libNitroModules.so
     9284  01-01-1981 01:01   base/lib/x86/libandroidx.graphics.path.so
   999384  01-01-1981 01:01   base/lib/x86/libappmodules.so
  1254988  01-01-1981 01:01   base/lib/x86/libc++_shared.so
     5148  01-01-1981 01:01   base/lib/x86/libdatastore_shared_counter.so
  1394984  01-01-1981 01:01   base/lib/x86/libexpo-modules-core.so
   107716  01-01-1981 01:01   base/lib/x86/libexpo-updates.so
   166480  01-01-1981 01:01   base/lib/x86/libfbjni.so
    34680  01-01-1981 01:01   base/lib/x86/libgesturehandler.so
   281448  01-01-1981 01:01   base/lib/x86/libgifimage.so
   136716  01-01-1981 01:01   base/lib/x86/libhermestooling.so
  3019744  01-01-1981 01:01   base/lib/x86/libhermesvm.so
     7040  01-01-1981 01:01   base/lib/x86/libimagepipeline.so
   437228  01-01-1981 01:01   base/lib/x86/libjsi.so
    22040  01-01-1981 01:01   base/lib/x86/libnative-filters.so
   664736  01-01-1981 01:01   base/lib/x86/libnative-imagetranscoder.so
  1244872  01-01-1981 01:01   base/lib/x86/libreact_codegen_rnscreens.so
   153476  01-01-1981 01:01   base/lib/x86/libreact_codegen_safeareacontext.so
  7362736  01-01-1981 01:01   base/lib/x86/libreactnative.so
  1428124  01-01-1981 01:01   base/lib/x86/libreanimated.so
    93560  01-01-1981 01:01   base/lib/x86/librnscreens.so
   550092  01-01-1981 01:01   base/lib/x86/libstatic-webp.so
   968828  01-01-1981 01:01   base/lib/x86/libworklets.so
   857780  01-01-1981 01:01   base/lib/x86/libzstd-kmp.so
   893392  01-01-1981 01:01   base/lib/x86_64/libNitroGoogleSignin.so
   967768  01-01-1981 01:01   base/lib/x86_64/libNitroModules.so
    10760  01-01-1981 01:01   base/lib/x86_64/libandroidx.graphics.path.so
  1019752  01-01-1981 01:01   base/lib/x86_64/libappmodules.so
  1252080  01-01-1981 01:01   base/lib/x86_64/libc++_shared.so
     6224  01-01-1981 01:01   base/lib/x86_64/libdatastore_shared_counter.so
  1443872  01-01-1981 01:01   base/lib/x86_64/libexpo-modules-core.so
   113552  01-01-1981 01:01   base/lib/x86_64/libexpo-updates.so
   172952  01-01-1981 01:01   base/lib/x86_64/libfbjni.so
    35608  01-01-1981 01:01   base/lib/x86_64/libgesturehandler.so
   302952  01-01-1981 01:01   base/lib/x86_64/libgifimage.so
   138128  01-01-1981 01:01   base/lib/x86_64/libhermestooling.so
  2591976  01-01-1981 01:01   base/lib/x86_64/libhermesvm.so
     8464  01-01-1981 01:01   base/lib/x86_64/libimagepipeline.so
   418872  01-01-1981 01:01   base/lib/x86_64/libjsi.so
    30528  01-01-1981 01:01   base/lib/x86_64/libnative-filters.so
   720432  01-01-1981 01:01   base/lib/x86_64/libnative-imagetranscoder.so
  1225744  01-01-1981 01:01   base/lib/x86_64/libreact_codegen_rnscreens.so
   159728  01-01-1981 01:01   base/lib/x86_64/libreact_codegen_safeareacontext.so
  7098048  01-01-1981 01:01   base/lib/x86_64/libreactnative.so
  1538464  01-01-1981 01:01   base/lib/x86_64/libreanimated.so
    97696  01-01-1981 01:01   base/lib/x86_64/librnscreens.so
   560968  01-01-1981 01:01   base/lib/x86_64/libstatic-webp.so
  1055472  01-01-1981 01:01   base/lib/x86_64/libworklets.so
   816568  01-01-1981 01:01   base/lib/x86_64/libzstd-kmp.so
AAB_UNZIP_INSPECTION=PASS

============================================================
5. CMS / DATABASE READ-ONLY PERFORMANCE BASELINE
============================================================
No syntax errors detected in /tmp/ald1n-perf-audit-wE72Z0/db-baseline.php
DB_DRIVER=mysql
DB_SESSION_READ_ONLY=PASS
DB_SELECT1_MEDIAN_MS=0.129
DB_SELECT1_P95_MS=0.164
DB_SELECT1_MAX_MS=0.408
TABLE_users_COUNT=16
TABLE_users_COUNT_QUERY_MS=9.280
TABLE_products_COUNT=39
TABLE_products_COUNT_QUERY_MS=0.850
TABLE_orders_COUNT=13
TABLE_orders_COUNT_QUERY_MS=0.719
TABLE_notifications_COUNT=404
TABLE_notifications_COUNT_QUERY_MS=0.907
TABLE_order_payments_COUNT=13
TABLE_order_payments_COUNT_QUERY_MS=0.683
TABLE_product_images_COUNT=252
TABLE_product_images_COUNT_QUERY_MS=0.564
TABLE_after_sales_cases_COUNT=0
TABLE_after_sales_cases_COUNT_QUERY_MS=0.713
TABLE_product_warranties_COUNT=15
TABLE_product_warranties_COUNT_QUERY_MS=1.124
TOP_DATABASE_TABLES_BEGIN
audit_logs rows=426 data=1589248 index=196608 total=1785856
order_email_outbox rows=252 data=1589248 index=163840 total=1753088
orders rows=13 data=16384 index=376832 total=393216
products rows=39 data=81920 index=294912 total=376832
automation_runs rows=683 data=212992 index=81920 total=294912
notifications rows=250 data=196608 index=98304 total=294912
legacy_audit_logs rows=153 data=147456 index=65536 total=212992
product_warranties rows=15 data=32768 index=147456 total=180224
system_health_snapshots rows=52 data=147456 index=32768 total=180224
order_payments rows=13 data=16384 index=147456 total=163840
data_quality_snapshots rows=3 data=114688 index=49152 total=163840
after_sales_actions rows=0 data=16384 index=131072 total=147456
order_documents rows=20 data=32768 index=114688 total=147456
users rows=16 data=16384 index=131072 total=147456
stock_movements rows=67 data=32768 index=114688 total=147456
user_login_sessions rows=126 data=65536 index=65536 total=131072
receivable_cases rows=0 data=16384 index=114688 total=131072
order_commissions rows=3 data=16384 index=114688 total=131072
security_events rows=91 data=65536 index=65536 total=131072
specification_fields rows=19 data=16384 index=114688 total=131072
product_images rows=252 data=98304 index=32768 total=131072
product_lines rows=321 data=65536 index=65536 total=131072
field_work_orders rows=0 data=16384 index=114688 total=131072
service_parts rows=0 data=16384 index=98304 total=114688
operational_alerts rows=24 data=16384 index=98304 total=114688
portal_conversations rows=0 data=16384 index=98304 total=114688
after_sales_cases rows=0 data=16384 index=98304 total=114688
warranty_rules rows=2 data=32768 index=81920 total=114688
stock_receipts rows=1 data=16384 index=81920 total=98304
courier_services rows=5 data=16384 index=81920 total=98304
TOP_DATABASE_TABLES_END
INDEXES_products_BEGIN
PRIMARY unique=YES cols=id
products_brand_id_index unique=NO cols=brand_id
products_catalog_active_created_v216_idx unique=NO cols=deleted_at,status,created_at
products_completeness_status_index unique=NO cols=completeness_percent,status
products_locally_modified_at_index unique=NO cols=locally_modified_at
products_model_name_index unique=NO cols=model_name
products_name_index unique=NO cols=name
products_owner_updated_v216_idx unique=NO cols=created_by,deleted_at,updated_at
products_price_amount_index unique=NO cols=price_amount
products_product_line_id_index unique=NO cols=product_line_id
products_product_type_id_index unique=NO cols=product_type_id
products_purchase_price_index unique=NO cols=purchase_price_rsd
products_sku_unique unique=YES cols=sku
products_slug_unique unique=YES cols=slug
products_source_product_index unique=NO cols=source_product_id
products_status_index unique=NO cols=status
products_stock_quantity_index unique=NO cols=stock_quantity
products_type_status_v216_idx unique=NO cols=product_type_id,deleted_at,status
products_updated_by_foreign unique=NO cols=updated_by
INDEXES_products_END
INDEXES_orders_BEGIN
orders_accepted_by_foreign unique=NO cols=accepted_by
orders_assigned_by_foreign unique=NO cols=assigned_by
orders_attention_queue_index unique=NO cols=supplier_user_id,accepted_at,status,created_at
orders_bank_account_id_index unique=NO cols=bank_account_id
orders_cancelled_by_foreign unique=NO cols=cancelled_by
orders_completion_state_index unique=NO cols=completed_at,status,payment_status
orders_created_at_index unique=NO cols=created_at
orders_direct_sale_recorded_by_foreign unique=NO cols=direct_sale_recorded_by
orders_inventory_state_index unique=NO cols=inventory_state
orders_order_number_unique unique=YES cols=order_number
orders_payment_attention_index unique=NO cols=payment_state,payment_due_at,created_at
orders_payment_method_payment_status_index unique=NO cols=payment_method,payment_status
orders_purged_at_index unique=NO cols=purged_at
orders_reopen_state_index unique=NO cols=reopened_at,completed_at
orders_sales_channel_index unique=NO cols=sales_channel
orders_source_system_index unique=NO cols=source_system
orders_status_index unique=NO cols=status
orders_supplier_status_created_index unique=NO cols=supplier_user_id,status,created_at
orders_tracking_number_index unique=NO cols=tracking_number
orders_tracking_updated_by_foreign unique=NO cols=tracking_updated_by
orders_updated_by_foreign unique=NO cols=updated_by
orders_user_idempotency_unique unique=YES cols=user_id,idempotency_key_hash
orders_user_id_index unique=NO cols=user_id
PRIMARY unique=YES cols=id
INDEXES_orders_END
INDEXES_order_items_BEGIN
order_items_brand_snapshot_index unique=NO cols=brand_name_snapshot
order_items_cost_source_index unique=NO cols=cost_source_snapshot
order_items_order_id_index unique=NO cols=order_id
order_items_product_id_foreign unique=NO cols=product_id
PRIMARY unique=YES cols=id
INDEXES_order_items_END
INDEXES_notifications_BEGIN
notifications_inbox_index unique=NO cols=notifiable_id,read_at,created_at
notifications_notifiable_type_notifiable_id_index unique=NO cols=notifiable_type,notifiable_id
PRIMARY unique=YES cols=id
INDEXES_notifications_END
INDEXES_order_payments_BEGIN
order_payments_after_sales_action_unique unique=YES cols=after_sales_action_id
order_payments_order_id_status_created_at_index unique=NO cols=order_id,status,created_at
order_payments_payment_method_status_index unique=NO cols=payment_method,status
order_payments_payment_number_unique unique=YES cols=payment_number
order_payments_rejected_by_foreign unique=NO cols=rejected_by
order_payments_status_paid_at_index unique=NO cols=status,paid_at
order_payments_submitted_by_foreign unique=NO cols=submitted_by
order_payments_verified_by_foreign unique=NO cols=verified_by
order_payments_voided_by_foreign unique=NO cols=voided_by
PRIMARY unique=YES cols=id
INDEXES_order_payments_END
CMS_DB_BASELINE=PASS_READ_ONLY

============================================================
5A. LARAVEL CACHE / OPTIMIZATION STATE
============================================================
LARAVEL_CACHE_FILE_config.php=ABSENT
LARAVEL_CACHE_FILE_events.php=ABSENT
LARAVEL_CACHE_FILE_routes-v7.php=PRESENT bytes=744878
LARAVEL_CACHE_FILE_services.php=PRESENT bytes=22215
LARAVEL_CACHE_FILE_packages.php=PRESENT bytes=548
ROUTE_TOTAL=509
ROUTE_API_COUNT=257
ROUTE_ADMIN_CONTAINING_COUNT=396
ROUTE_LIST_BASELINE=PASS

============================================================
5B. LARGE CMS APP FILES BY LOC
============================================================
00000862 000000030978 app/Services/TotalProductPurgeService.php
00000802 000000034520 app/Http/Controllers/Api/V1/Admin/CatalogProductController.php
00000784 000000035812 app/Services/OrderDetailPresenter.php
00000735 000000037290 app/Services/OperationalAutomationService.php
00000718 000000032232 app/Services/Pdf/SimplePdfWriter.php
00000656 000000030715 app/Services/CatalogDictionaryManagerService.php
00000648 000000022158 app/Http/Controllers/Api/V1/Admin/WarrantyController.php
00000638 000000032853 app/Console/Commands/DeploymentCheckCommand.php
00000635 000000029493 app/Services/Pdf/BusinessDocumentPdfService.php
00000631 000000031168 app/Services/OrderWorkflowService.php
00000618 000000021549 app/Services/TotalProductPurgeVerifier.php
00000606 000000021051 app/Services/GlobalCommandSearchService.php
00000554 000000021564 app/Console/Commands/ReleaseCheckCommand.php
00000543 000000027663 app/Http/Controllers/DashboardController.php
00000501 000000027992 app/Services/ManagementReportService.php
00000496 000000024449 app/Services/OrderDocumentService.php
00000495 000000020283 app/Http/Controllers/Api/V1/Admin/ReceivablesController.php
00000491 000000028930 app/Services/ServicePartsInventoryService.php
00000487 000000022424 app/Http/Controllers/Admin/OrderController.php
00000484 000000025485 app/Services/ReceivablesService.php
00000444 000000020716 app/Services/StorageSpecificationService.php
00000437 000000020003 app/Http/Controllers/Api/V1/Admin/AfterSalesController.php
00000432 000000018778 app/Http/Controllers/Api/V1/Admin/FieldOperationsController.php
00000429 000000022783 app/Services/ProductAdminService.php
00000427 000000022769 app/Services/AfterSalesActionService.php
00000426 000000018979 app/Services/DirectSaleService.php
00000422 000000019924 app/Services/DataQualityService.php
00000421 000000017461 app/Http/Controllers/Api/V1/Admin/InventoryController.php
00000420 000000019956 app/Http/Controllers/Admin/SiteAppearanceController.php
00000405 000000020887 app/Services/AfterSalesCaseService.php
00000405 000000017344 app/Services/ProductImageService.php
00000399 000000017260 app/Services/BrandManagerService.php
00000394 000000015218 app/Console/Commands/CmsV215DoctorCommand.php
00000383 000000015730 app/Http/Controllers/Api/V1/OrderController.php
00000382 000000020524 app/Services/BrandLineCatalogService.php
00000370 000000013605 app/Services/OrderItemCostSnapshotService.php
00000368 000000019926 app/Services/CustomerPortalService.php
00000367 000000015515 app/Http/Controllers/Api/V1/AfterSalesController.php
00000365 000000019141 app/Http/Controllers/Api/V1/Admin/SystemSettingsController.php
00000364 000000018174 app/Http/Controllers/Api/V1/Admin/ServicePartsController.php
00000345 000000019082 app/Services/OrderPaymentService.php
00000338 000000015368 app/Http/Controllers/Admin/ReportController.php
00000336 000000018013 app/Http/Controllers/Admin/ReceivablesController.php
00000336 000000015807 app/Services/OrderEmailOutboxService.php
00000335 000000012954 app/Http/Controllers/Api/V1/Admin/CommissionController.php
00000329 000000013984 app/Http/Controllers/Auth/GoogleWebAuthController.php
00000320 000000010495 app/Http/Controllers/Api/V1/Admin/SystemHealthController.php
00000319 000000012164 app/Console/Commands/OrdersDoctorCommand.php
00000313 000000016973 app/Providers/AppServiceProvider.php
00000312 000000012225 app/Http/Controllers/Api/V1/Admin/OrderController.php

============================================================
5C. CMS QUERY / PAGINATION STATIC SIGNALS
============================================================
CMS_PAGINATE_REFERENCES=47
CMS_GET_REFERENCES=349
CMS_WITH_REFERENCES=353
CMS_CURSOR_REFERENCES=3
CMS_CHUNK_REFERENCES=22
app/Services/CatalogQueryService.php:183:        return $query->paginate(max(6, min($safeMaximum, $perPage)))->withQueryString();
app/Services/CommissionReportService.php:45:        return $this->managedQuery($actor, $filters)->paginate($perPage)->withQueryString();
app/Services/CommissionReportService.php:51:        return $this->ownQuery($user, $filters)->paginate($perPage)->withQueryString();
app/Services/OrderIndexService.php:115:        return $query->paginate($perPage)->withQueryString();
app/Services/OrderReportService.php:113:        return $this->query($user, $filters)->paginate($perPage)->withQueryString();
app/Services/OrderArchiveService.php:49:        return $query->paginate(max(10, min(100, $perPage)))->withQueryString();
app/Http/Controllers/Admin/ServicePartController.php:35:            ->orderBy('name')->paginate(50)->withQueryString();
app/Http/Controllers/Admin/FieldServiceTeamController.php:29:            ->orderByDesc('is_active')->orderBy('name')->paginate(40)->withQueryString();
app/Http/Controllers/Admin/ReportController.php:44:            $orders = $reports->paginate($user, $filters);
app/Http/Controllers/Admin/StockMovementController.php:38:            'movements' => $query->paginate(50)->withQueryString(),
app/Http/Controllers/Admin/AuditLogController.php:29:            'logs' => $query->paginate(40)->withQueryString(),
app/Http/Controllers/Admin/ProductController.php:52:            'products' => $query->orderByDesc('deleted_at')->orderByDesc('id')->paginate(30)->withQueryString(),
app/Http/Controllers/Admin/ServicePartSupplierController.php:26:            })->orderByDesc('is_active')->orderBy('name')->paginate(50)->withQueryString();
app/Http/Controllers/Admin/OrderController.php:54:                'orders' => $orders->paginate($actor, $filters, $access),
app/Http/Controllers/Admin/UserController.php:36:            'users' => $query->paginate(30)->withQueryString(),
app/Http/Controllers/Admin/AfterSalesController.php:50:            'cases' => $query->paginate(40)->withQueryString(),
app/Http/Controllers/Admin/WarrantyController.php:59:            'warranties' => $query->paginate(40)->withQueryString(),
app/Http/Controllers/Admin/CustomerPortalController.php:64:            'users' => $query->paginate(25)->withQueryString(),
app/Http/Controllers/Admin/ReceivablesController.php:77:            'cases' => $query->paginate(35)->withQueryString(),
app/Http/Controllers/Admin/ServicePartPurchaseRequestController.php:31:            'purchaseRequests' => $query->paginate(40)->withQueryString(),
app/Http/Controllers/NotificationController.php:16:            'notifications' => $request->user()->notifications()->latest()->paginate(40),
app/Http/Controllers/Api/V1/Admin/ServicePartsController.php:43:        $page = $query->orderBy('sku')->paginate($perPage)->withQueryString();
app/Http/Controllers/Api/V1/Admin/ServicePartsController.php:93:        $page = $query->orderBy('name')->paginate($perPage)->withQueryString();
app/Http/Controllers/Api/V1/Admin/ServicePartsController.php:130:        $page = $query->latest('id')->paginate($perPage)->withQueryString();
app/Http/Controllers/Api/V1/Admin/InventoryController.php:56:        $products = $query->paginate((int) ($filters['per_page'] ?? 35))->withQueryString();
app/Http/Controllers/Api/V1/Admin/InventoryController.php:126:        $movements = $query->paginate((int) ($filters['per_page'] ?? 50))->withQueryString();
app/Http/Controllers/Api/V1/Admin/CatalogProductController.php:630:            ->paginate($perPage)
app/Http/Controllers/Api/V1/Admin/OrderController.php:59:            $paginator = $orders->paginate($actor, $validated, $access, $perPage);
app/Http/Controllers/Api/V1/Admin/UserController.php:54:        $paginator = $query->paginate($perPage);
app/Http/Controllers/Api/V1/Admin/AfterSalesController.php:67:        $cases = $query->paginate((int) ($filters['per_page'] ?? 40))->withQueryString();
app/Http/Controllers/Api/V1/Admin/WarrantyController.php:47:        $paginator = $query->paginate($perPage);
app/Http/Controllers/Api/V1/Admin/AuditEventController.php:52:        $paginator = $events->query($request)->paginate($perPage);
app/Http/Controllers/Api/V1/Admin/FieldOperationsController.php:84:        $workOrders = $query->paginate((int) ($filters['per_page'] ?? 40))->withQueryString();
app/Http/Controllers/Api/V1/Admin/ReceivablesController.php:67:        $cases = $query->paginate((int) ($filters['per_page'] ?? 35))->withQueryString();
app/Http/Controllers/Api/V1/NotificationController.php:23:        return NotificationResource::collection($query->paginate($perPage)->withQueryString());
app/Http/Controllers/Api/V1/OrderController.php:34:            Order::query()->operational()->where('user_id', $request->user()->id)->with(['items', 'commission', 'supplier'])->latest('id')->paginate(30)
app/Http/Controllers/Api/V1/OrderController.php:45:                ->paginate(30)
app/Http/Controllers/Api/V1/AfterSalesController.php:28:        $cases = $query->paginate(30);
app/Http/Controllers/Api/V1/CatalogController.php:17:        return ProductResource::collection($catalog->paginate($request->user(), $request->query(), (int) $request->integer('per_page', 20)));
app/Http/Controllers/Api/V1/WarrantyController.php:26:            ->paginate(30);
app/Http/Controllers/PortalConversationController.php:31:                ->paginate(20)
app/Http/Controllers/OrderController.php:41:                ->paginate(30),
app/Http/Controllers/AfterSalesController.php:26:            'cases' => $query->paginate(30),
app/Http/Controllers/CatalogController.php:54:        $products = $catalog->paginate(
app/Http/Controllers/WarrantyController.php:21:            'warranties' => ProductWarranty::query()->with(['order', 'orderItem.order'])->ownedByOrderCustomer((int) $request->user()->id)->latest('id')->paginate(30),
app/Console/Commands/ReportsDoctorCommand.php:83:            $page = $reports->paginate($user, [], 5);
app/Console/Commands/OrdersDoctorCommand.php:56:            $page = $orders->paginate($actor, [], $access, 5);

============================================================
6. FINAL READ-ONLY CERTIFICATION
============================================================
Backup: /home/icaffeco/backups/current/ALD1N-CLEAN-STABLE-v1.0.0-cms-v2.2.0-20260828-112307/cms-runtime/20260828-112345-manual-f8aad0
PASS Backup verzija: 2.2.0.
PASS Backup je svez: 0,4 h.
PASS SQL backup SHA-256 je validan.
PASS SQL gzip je citljiv; dekompresovano 2,35 MB.
PASS SQL backup sadrzi CREATE TABLE.
PASS SQL backup sadrzi migrations tabela.
PASS Verifikovani privatni backup fajlovi: 252/252.
PASS Backup evidencija i velicina direktorijuma su uskladjene: 460,38 MB.
Backup je logicki validan i spreman za kontrolisanu restore probu na odvojenoj bazi.
SOURCE_AUTHORITY_POST=PASS_ONLY_CANONICAL_HTACCESS_DRIFT
CLEAN_STABLE_POST_AUDIT_VERIFY=PASS_RUN88
CMS_STATIC_CHECK=PASS_983_OF_983
MOBILE_VALIDATE=PASS
MOBILE_TYPECHECK=PASS_TSC_NO_EMIT
STRICT_PARITY=PRESERVED_62_OF_62_100_PERCENT
SOURCE_MUTATION=REPORT_ONLY
DATABASE_WRITES=0
MIGRATIONS_RUN=NO
EAS_BUILD_COMMANDS_RUN=0
BUILD14_CREATED=NO
BATCH62_RESULT=PASS_READ_ONLY_BASELINE_AUDIT_COMPLETE
OPTIMIZATION_IMPLEMENTATION_STARTED=NO
NEXT_ACTION=ANALYZE_REPORT279_AND_BUILD_FIRST_MEASURED_OPTIMIZATION_BATCH63_WITHOUT_EAS_BUILD
REPORT=/home/icaffeco/ald1n-project/docs/operations/279-MOBILE-V1.0-PERFORMANCE-OPTIMIZATION-READ-ONLY-BASELINE-AUDIT-BATCH62-20260828-114426.md
REPORT279_PREHASH_SHA256=7e0d34912e3440b959eae4d33d828c2e80dc917a3aff3e741fcead1dd403d6bb
