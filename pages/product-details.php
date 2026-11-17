<?php
// session_start();

// if(!isset($_SESSION['user_id'])){
//     header("Location: ../../../../login.php");
//     exit;
// }
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ServEasy - Service Details</title>

  <!-- 
    - favicon
  -->
  <link rel="shortcut icon" href="./favicon.svg" type="image/svg+xml">

  <!-- 
    - custom css link
  -->
  <link rel="stylesheet" href="./assets/css/main.css">
  <link rel="stylesheet" href="./assets/css/global.css">
  <link rel="stylesheet" href="./assets/css/product-details.css">

  <!-- 
    - google font link
  -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Roboto:wght@400;500;700&display=swap"
    rel="stylesheet">
</head>

<body id="top">

  <!-- 
    - #HEADER
  -->

  <?php include __DIR__ . '/layout/header.php'; ?>



  <!-- 
    - #ASIDE
  -->

  <?php include __DIR__ . '/constants/aside.php'; ?>



  <main>
    <article>

      <!-- 
        - #BREADCUMB
      -->

      <div class="breadcumb-wrapper">

        <h2 class="page-title">Product Details</h2>

        <ol class="breadcumb-list">

          <li class="breadcumb-item">
            <a href="./index.html" class="breadcumb-link">Home</a>
          </li>

          <li class="breadcumb-item">Product</li>

        </ol>

      </div>





      <!-- 
        - #PRODUCT DETAILS
      -->

      <section class="section product-details">
        <div class="container">

          <div class="wrapper">

            <div class="product-details-img">

              <figure class="product-display">
                <img src="./assets/images/product-details-1.jpg" width="700" height="700" loading="lazy"
                  alt="Non-starchy vegetables." class="w-100" data-product-display>
              </figure>

              <ul class="product-thumbnail-list has-scrollbar">

                <li class="product-thumbnail-item">
                  <img src="./assets/images/product-details-1.jpg" width="700" height="700" loading="lazy"
                    alt="Non-starchy vegetables." class="w-100" data-product-thumbnail>
                </li>

                <li class="product-thumbnail-item">
                  <img src="./assets/images/product-details-2.jpg" width="700" height="700" loading="lazy"
                    alt="Non-starchy vegetables." class="w-100" data-product-thumbnail>
                </li>

                <li class="product-thumbnail-item">
                  <img src="./assets/images/product-details-3.jpg" width="700" height="700" loading="lazy"
                    alt="Non-starchy vegetables." class="w-100" data-product-thumbnail>
                </li>

                <li class="product-thumbnail-item">
                  <img src="./assets/images/product-details-2.jpg" width="700" height="700" loading="lazy"
                    alt="Non-starchy vegetables." class="w-100" data-product-thumbnail>
                </li>

              </ul>

            </div>

            <div class="product-details-content">

              <h3 class="product-title">Non-starchy vegetables.</h3>

              <data class="product-price" value="350.00">350.00 USD</data>

              <div class="rating-wrapper">
                <ion-icon name="star"></ion-icon>
                <ion-icon name="star"></ion-icon>
                <ion-icon name="star"></ion-icon>
                <ion-icon name="star"></ion-icon>
                <ion-icon name="star"></ion-icon>
              </div>

              <p class="product-text">
                Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc quis ultrices lectus lobortis, dolor et
                tempus porta, leo
                mi efficitur ante, in varius felis sem ut mauris. Proin volutpat lorem inorci sed vestibulum tempus.
                Lorem
                ipsum dolor
                sit amet, consectetur adipiscing elit. Aliquam hendrerit sem porta dolor congue sagittis Lorem ipsum
                dolor
                sit amet
                consectetur.
              </p>

              <div class="product-weight-wrapper">

                <p class="product-weight-title">Weight :</p>

                <ul class="product-weight-list">

                  <li class="product-weight-item">
                    <input type="radio" name="weight" id="weight-1" class="product-weight-radio">

                    <label for="weight-1" class="product-weight-label">4L</label>
                  </li>

                  <li class="product-weight-item">
                    <input type="radio" name="weight" id="weight-2" class="product-weight-radio">

                    <label for="weight-2" class="product-weight-label">2L</label>
                  </li>

                  <li class="product-weight-item">
                    <input type="radio" name="weight" id="weight-3" class="product-weight-radio">

                    <label for="weight-3" class="product-weight-label">500ML</label>
                  </li>

                  <li class="product-weight-item">
                    <input type="radio" name="weight" id="weight-4" class="product-weight-radio">

                    <label for="weight-4" class="product-weight-label">200ML</label>
                  </li>

                </ul>

              </div>

              <div class="product-qty">
                <input type="number" name="quantity" value="1" min="1" max="50" class="product-qty-input">

                <button class="btn btn-primary product-qty-btn">Add To Cart</button>
              </div>

            </div>

          </div>

          <h4 class="description-title">Descripton :</h4>

          <p class="description-text">
            Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem
            aperiam,
            eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim
            ipsam
            voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui
            ratione
            voluptatem sequi nesciunt. Neque porro quisquam.Sed ut perspiciatis unde omnis iste natus error sit
            voluptatem
            accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi
            architecto
            beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut
            fugit,
            sed quia
            consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam
          </p>

          <h4 class="description-title">Additional Information :</h4>

          <table class="description-table" border="1px">

            <tr class="table-row">
              <th class="table-heading" scope="row">Ratings</th>

              <td class="table-data">
                <div class="rating-wrapper">
                  <ion-icon name="star"></ion-icon>
                  <ion-icon name="star"></ion-icon>
                  <ion-icon name="star"></ion-icon>
                  <ion-icon name="star-half"></ion-icon>
                  <ion-icon name="star-outline"></ion-icon>
                </div>
              </td>
            </tr>

            <tr class="table-row">
              <th class="table-heading" scope="row">Material Type</th>

              <td class="table-data">Pomagranate Fruit</td>
            </tr>

            <tr class="table-row">
              <th class="table-heading" scope="row">Weight</th>

              <td class="table-data">250 gm</td>
            </tr>

            <tr class="table-row">
              <th class="table-heading" scope="row">Seller</th>

              <td class="table-data">ServEasy</td>
            </tr>

            <tr class="table-row">
              <th class="table-heading" scope="row">Size</th>

              <td class="table-data">6KG</td>
            </tr>

          </table>

        </div>
      </section>

    </article>
  </main>





  <!-- 
    - #FOOTER
  -->

  <?php include __DIR__ . '/layout/footer.php'; ?>



  <!-- 
    - #BACK TO TOP
  -->

  <a href="#top" class="back-to-top" aria-label="Back to Top" data-back-top-btn>
    <ion-icon name="arrow-up-outline"></ion-icon>
  </a>





  <!-- 
    - custom js link
  -->
  <script src="./assets/js/script.js"></script>

  <!-- 
    - ionicon link
  -->
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>

</body>

</html>