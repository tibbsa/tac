# Tactile Acuity Chart Generators

Tools to generate images of various types of "tactile acuity charts" used
primarily in research to measure tactile finger sensitivity and
discrimination capabilities.

Currently included charts are:

- Legge et al.'s "Landolt C" chart
- Legge et al.'s "Dot" chart

## Usage

Each tool generates two .PNG files in the current/working directory containing
the printable chart contents. One of the charts (_labelled.png) has text
labels on each line for identification purposes.

Simply run PHP from the command line to generate the chart.

```
$ php make_dot.php
$ php make_landolt.php
```

## System Requirements

- Tested with PHP 7.0.32
- Requires that GD be available to PHP

## License

This project is licensed under the GPL v3 License.  See [LICENSE.md](LICENSE.md) for details.

The GNU Free Font is included for labelling.

## References

Legge, G. et al. (2008) Retention of high tactile acuity throughout the lifespan in blindness. *Perception & Psychophysics, 70*(8): 1471-1488. https://www.ncbi.nlm.nih.gov/pmc/articles/PMC3611958/

Bruns, P. et al. (2014) Tactile acuity charts: A reliable measure of spatial acuity. *PLOS ONE 9*(2): e87384. https://doi.org/10.1371/journal.pone.0087384

