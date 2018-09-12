<?php

namespace Movies;

class Totoro {
  public function playMovie() {
    Totoro\startMoviePlayer(); // this should be a warning that `Totoro` is not imported
  }
}
