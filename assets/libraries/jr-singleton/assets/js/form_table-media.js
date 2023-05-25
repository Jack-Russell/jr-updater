jQuery( function($) {

  const $wrapper = $( 'td.image' );

  $wrapper.each( function( i, elem ) {

    const $set    = $(elem).find( '.image--set' )
    const $delete = $(elem).find( '.image--delete' )

    const $id     = $(elem).find( 'input[type="hidden"]' )
    const $image  = $(elem).find( '.image--image' + ' ' + 'img' )

    $set.on( 'click', function(e) {

      e.preventDefault()

      uploader = wp.media( {
        title: 'Select Image',

        button: {
          text: 'Select Image'
        },

        library: {
          type: 'image'
        },

        multiple: false
      } )

      uploader.on( 'select', function() {

        const images = uploader.state().get( 'selection' )

        images.each( function( data ) {
          $id.val( data.id )
          $image.attr( 'src', data.attributes.url )
        })

      })

      uploader.open()

    } )

    $delete.on( 'click', function(e) {

      e.preventDefault()

      $id.val( '' )
      $image.attr( 'src', '' )

    } )

  } )

} )
